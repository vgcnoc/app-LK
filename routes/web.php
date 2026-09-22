<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\Models\Material;
use App\Models\CompanyExpenseType;
use App\Models\Reseller;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/register-affiliate', function () {
        $areas = \App\Models\Area::pluck('name');
        return Inertia::render('Auth/RegisterAffiliate', [
            'areas' => $areas,
        ]);
    })->name('register.affiliate');

    Route::post('/register-affiliate', function (Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users|unique:sales',
            'phone' => 'nullable|string|max:255',
            'bank_account' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'upline_code' => 'required|string',
        ]);

        $parent_id = null;
        if ($request->upline_code) {
            $upline = \App\Models\Sales::where('member_number', $request->upline_code)->first();
            if ($upline) {
                $parent_id = $upline->id;
            } else {
                return back()->withErrors(['upline_code' => 'Kode Referral tidak ditemukan.']);
            }
        }

        // Generate Member Number (e.g. SL-20231015-0001)
        $date = date('Ymd');
        $lastSale = \App\Models\Sales::where('member_number', 'like', "SL-{$date}-%")->orderBy('id', 'desc')->first();
        if ($lastSale && preg_match('/-(\d+)$/', $lastSale->member_number, $matches)) {
            $nextSequence = intval($matches[1]) + 1;
        } else {
            $nextSequence = 1;
        }
        $member_number = sprintf("SL-%s-%04d", $date, $nextSequence);

        // Create User
        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => 'sales',
        ]);
        $user->assignRole('sales');

        // Create Sales Profile
        \App\Models\Sales::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'member_number' => $member_number,
            'phone' => $request->phone,
            'email' => $request->email,
            'bank_account' => $request->bank_account,
            'status' => 'Aktif',
            'parent_id' => $parent_id,
            'join_date' => date('Y-m-d'),
        ]);

        return redirect()->route('login')->with('status', 'Pendaftaran berhasil! Silakan masuk dengan email dan kata sandi Anda.');
    });
});

Route::middleware(['auth'])->group(function () {
    
    // DASHBOARD
    Route::get('/dashboard', function (Request $request) {
        $user = auth()->user();
        if ($user && $user->hasRole('sales')) {
            $mySalesProfile = \App\Models\Sales::where('user_id', $user->id)->first();
            $totalDownlines = 0;
            if ($mySalesProfile) {
                $totalDownlines = \App\Models\Sales::where('parent_id', $mySalesProfile->id)->count();
            }
            
            // Dummy or actual metrics for sales
            $totalBooking = 0;
            $activeBooking = 0;
            $pendingBooking = 0;

            if ($mySalesProfile) {
                // Booking status is typically 'Booking' or 'Aktif' depending on if it has been activated.
                $totalBooking = \App\Models\Customer::where('sales_id', $mySalesProfile->id)->count(); 
                $activeBooking = \App\Models\Customer::where('sales_id', $mySalesProfile->id)->where('status_pelanggan', 'Aktif')->count();
                $pendingBooking = \App\Models\Customer::where('sales_id', $mySalesProfile->id)->where('status_pelanggan', 'Booking')->count();
                $pasangBerbayar = \App\Models\Customer::where('sales_id', $mySalesProfile->id)->where('installation_fee', '>', 0)->count();
                $pasangGratis = \App\Models\Customer::where('sales_id', $mySalesProfile->id)->where(function($q) {
                    $q->where('installation_fee', '<=', 0)->orWhereNull('installation_fee');
                })->count();
            }
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
            return Inertia::render('SalesDashboard', [
                'profile' => $mySalesProfile,
                'globalInstallationFee' => $settings['global_installation_fee'] ?? null,
                'metrics' => [
                    'totalDownlines' => $totalDownlines,
                    'totalBooking' => $totalBooking,
                    'activeBooking' => $activeBooking,
                    'pendingBooking' => $pendingBooking,
                    'pasangBerbayar' => $pasangBerbayar ?? 0,
                    'pasangGratis' => $pasangGratis ?? 0,
                    'totalCommission' => 0, // Placeholder
                ]
            ]);
        }
        
        \App\Models\Customer::syncBilling();

        $query = Transaction::query();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }
        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        // Global summaries
        // For calculations without kategori filter (but we need to separate income and expense)
        // Note: For income totals, we must only include "paid" transactions.
        $totalIncome = (clone $query)->where('type', 'income')
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereNull('payment_status');
            })->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // Area summaries
        $areaSummaries = (clone $query)->select('area', 
            DB::raw("SUM(CASE WHEN type = 'income' AND (payment_status = 'paid' OR payment_status IS NULL) THEN amount ELSE 0 END) as total_income"),
            DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense")
        )->groupBy('area')->get()->map(function($item) {
            return [
                'area_name' => $item->area ?: 'Tanpa Area',
                'total_income' => (float)$item->total_income,
                'total_expense' => (float)$item->total_expense,
            ];
        });

        // Chart data (last 7 days)
        $chartLabels = [];
        $chartIncome = [];
        $chartExpense = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartLabels[] = now()->subDays($i)->format('d M');
            
            $income = Transaction::where('date', $date)->where('type', 'income')
                ->where(function($q) {
                    $q->where('payment_status', 'paid')
                      ->orWhereNull('payment_status');
                })->sum('amount');
            $expense = Transaction::where('date', $date)->where('type', 'expense')->sum('amount');
            
            $chartIncome[] = $income;
            $chartExpense[] = $expense;
        }

        // Recent transactions
        $transactions = Transaction::where(function($q) {
                $q->where('type', '!=', 'income')
                  ->orWhere(function($q2) {
                      $q2->where('type', 'income')
                         ->where(function($q3) {
                             $q3->where('payment_status', 'paid')
                                ->orWhereNull('payment_status');
                         });
                  });
            })
            ->orderBy('date', 'desc')->orderBy('id', 'desc')->limit(10)->get();

        // Payment Methods
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        // Belum Lunas stats
        $unpaidQuery = Customer::whereIn('status', ['pending', 'nunggak'])
            ->where(function($q) {
                $q->where('status_pelanggan', 'Aktif')
                  ->orWhereNull('status_pelanggan')
                  ->orWhere('status_pelanggan', '');
            });
        $unpaidCount = $unpaidQuery->count();
        $unpaidTotal = $unpaidQuery->sum('amount');

        // Expense Categories
        $expenseCategories = App\Models\ExpenseCategory::orderBy('name')->get();
        $materials = Material::orderBy('name')->get();
        $companyExpenseTypes = CompanyExpenseType::orderBy('name')->get();

        // Customer Stats
        $customerTotal = Customer::count();
        $customerAktif = Customer::where('status_pelanggan', 'Aktif')->orWhereNull('status_pelanggan')->orWhere('status_pelanggan', '')->count();
        $customerNonaktif = Customer::whereIn('status_pelanggan', ['Nonaktif', 'Berhenti', 'Stop Permanen'])->count();
        $customerSuspend = Customer::whereIn('status_pelanggan', ['Suspend', 'Berhenti sementara', 'Isolir'])->count();
        $customerJanjiBayar = Customer::where('status_pelanggan', 'Janji Bayar')->count();
        $customerBayarSebagian = Customer::where('is_partial_payment', 1)
            ->whereIn('status', ['pending', 'nunggak', 'prorata'])
            ->where(function($q) {
                $q->where('status_pelanggan', 'Aktif')
                  ->orWhereNull('status_pelanggan')
                  ->orWhere('status_pelanggan', '');
            })->count();
        
        $customerPasangBerbayar = Customer::where('installation_fee', '>', 0)->count();
        $customerPasangGratis = Customer::where(function($q) {
            $q->where('installation_fee', '<=', 0)->orWhereNull('installation_fee');
        })->count();

        $customerStats = [
            'total' => $customerTotal,
            'aktif' => $customerAktif,
            'nonaktif' => $customerNonaktif,
            'suspend' => $customerSuspend,
            'janji_bayar' => $customerJanjiBayar,
            'bayar_sebagian' => $customerBayarSebagian,
            'pasang_berbayar' => $customerPasangBerbayar,
            'pasang_gratis' => $customerPasangGratis,
        ];

        // Recent Customers
        $recentCustomersQuery = Customer::orderBy('created_at', 'desc');
        if ($request->filled('area')) {
            $recentCustomersQuery->where('area', $request->area);
        }
        $recentCustomers = $recentCustomersQuery->limit(5)->get();

        // Areas for filter
        $areas = \App\Models\Area::orderBy('name')->pluck('name');

        // Overdue Bills (Filtered by global settings)
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $globalDueDate = $settings['global_due_date'] ?? null;
        $globalDueTime = $settings['global_due_time'] ?? '23:59';

        $allUnpaidQuery = Customer::whereIn('status', ['pending', 'nunggak'])
            ->where(function($q) {
                $q->where('status_pelanggan', 'Aktif')
                  ->orWhereNull('status_pelanggan')
                  ->orWhere('status_pelanggan', '');
            });
            
        if ($request->filled('area')) {
            $allUnpaidQuery->where('area', $request->area);
        }

        $allUnpaid = $allUnpaidQuery->get();

        $overdueBills = $allUnpaid->filter(function($c) use ($globalDueDate, $globalDueTime) {
            $today = \Carbon\Carbon::now();
            
            if ($c->promise_date) {
                $pd = \Carbon\Carbon::parse($c->promise_date)->endOfDay();
                if ($today->greaterThan($pd)) return true;
            }
            
            if ($globalDueDate) {
                if ($today->day > $globalDueDate) return true;
                
                if ($today->day == $globalDueDate) {
                    try {
                        $dueDateTime = \Carbon\Carbon::createFromFormat('H:i', $globalDueTime);
                        if ($today->greaterThan($dueDateTime)) return true;
                    } catch (\Exception $e) {}
                }
            }
            
            return false;
        })->sortBy(function($c) {
            return $c->promise_date ? \Carbon\Carbon::parse($c->promise_date)->timestamp : 9999999999;
        })->take(5)->values();

        // Percentages (simplified dummy calculation for UI)
        $percentages = [
            'income' => 0,
            'expense' => 0,
            'balance' => 0
        ];

        // Package distribution (Top Packages)
        $paketStats = \App\Models\Customer::whereNotIn('status_pelanggan', ['Booking', 'Berhenti', 'Stop Permanen', 'Nonaktif'])
            ->whereNotNull('paket')
            ->where('paket', '!=', '')
            ->select('paket', \DB::raw('count(*) as total'))
            ->groupBy('paket')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'globalInstallationFee' => $settings['global_installation_fee'] ?? null,
            'paketStats' => $paketStats,
            'filters' => $request->only(['area']),
            'areas' => $areas,
            'summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'balance' => $balance
            ],
            'percentages' => $percentages,
            'customerStats' => $customerStats,
            'recentCustomers' => $recentCustomers,
            'overdueBills' => $overdueBills,
            'areaSummaries' => $areaSummaries,
            'chart' => [
                'labels' => $chartLabels,
                'income' => $chartIncome,
                'expense' => $chartExpense,
            ],
            'transactions' => $transactions->load('expenseCategory'),
            'paymentMethods' => $paymentMethods,
            'expenseCategories' => $expenseCategories,
            'unpaid' => [
                'count' => $unpaidCount,
                'total' => (float)$unpaidTotal,
            ],
            'materials' => $materials,
            'companyExpenseTypes' => $companyExpenseTypes
        ]);
    })->name('dashboard');

    Route::get('/transaksi', function () {
        $userArea = auth()->user()->area;
        $isAdmin = auth()->user()->role === 'admin';

        $transactionsQuery = \App\Models\Transaction::with(['expenseCategory', 'companyExpenseType', 'material', 'customer'])->latest('date');

        if (!$isAdmin && $userArea) {
            $transactionsQuery->where('area', $userArea);
        }

        $transactions = $transactionsQuery->get();

        $areaSummaries = \App\Models\Transaction::selectRaw('
                area as area_name,
                SUM(CASE WHEN type = "income" AND (payment_status = "paid" OR payment_status IS NULL) THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense
            ')
            ->when(!$isAdmin && $userArea, function ($q) use ($userArea) {
                return $q->where('area', $userArea);
            })
            ->groupBy('area')
            ->get();

        $expenseCategories = \App\Models\ExpenseCategory::all();
        $companyExpenseTypes = \App\Models\CompanyExpenseType::all();
        $materials = \App\Models\Material::all();
        $paymentMethods = \App\Models\PaymentMethod::all();
        $incomeCategories = \App\Models\IncomeCategory::all();

        return Inertia::render('Transaksi', [
            'transactions' => $transactions,
            'areaSummaries' => $areaSummaries,
            'expenseCategories' => $expenseCategories,
            'companyExpenseTypes' => $companyExpenseTypes,
            'materials' => $materials,
            'paymentMethods' => $paymentMethods,
            'incomeCategories' => $incomeCategories,
            'areas' => \App\Models\Area::orderBy('name')->pluck('name'),
            'customers' => \App\Models\Customer::where('status_pelanggan', '!=', 'Booking')->orderBy('name')->get(['id', 'name', 'area', 'created_at']),
        ]);
    })->name('transaksi');

    // TRANSACTIONS
    Route::post('/transactions', function (Request $request) {
        $data = $request->validate([
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
            'paid_at' => 'nullable|date',
            'area' => 'nullable|string',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'company_expense_type_id' => 'nullable|exists:company_expense_types,id',
            'material_id' => 'nullable|exists:materials,id',
            'income_category_id' => 'nullable|exists:income_categories,id',
            'customer_id' => 'nullable|exists:customers,id'
        ]);
        
        if ($data['type'] === 'income') {
            $data['expense_category_id'] = null;
            $data['company_expense_type_id'] = null;
            $data['material_id'] = null;
        } else {
            $data['income_category_id'] = null;
            $data['customer_id'] = null;
        }

        Transaction::create($data);
        return back()->with('success', 'Transaksi berhasil ditambahkan.');
    })->name('transactions.store');

    Route::put('/transactions/{transaction}', function (Request $request, Transaction $transaction) {
        $data = $request->validate([
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
            'paid_at' => 'nullable|date',
            'area' => 'nullable|string',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'company_expense_type_id' => 'nullable|exists:company_expense_types,id',
            'material_id' => 'nullable|exists:materials,id',
            'income_category_id' => 'nullable|exists:income_categories,id',
            'customer_id' => 'nullable|exists:customers,id'
        ]);
        
        if ($data['type'] === 'income') {
            $data['expense_category_id'] = null;
            $data['company_expense_type_id'] = null;
            $data['material_id'] = null;
        } else {
            $data['income_category_id'] = null;
            $data['customer_id'] = null;
        }

        $transaction->update($data);
        return back()->with('success', 'Transaksi berhasil diperbarui.');
    })->name('transactions.update');

    Route::delete('/transactions/{transaction}', function (Request $request, Transaction $transaction) {
        if (!$request->user()->can('hapus_billing')) abort(403);
        $transaction->delete();
        return back()->with('success', 'Transaksi dihapus.');
    })->name('transactions.destroy');

    // MASTER DATA
    Route::get('/master-data', function () {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('MasterData', [
            'expenseCategories' => App\Models\ExpenseCategory::orderBy('name')->get(),
            'incomeCategories' => App\Models\IncomeCategory::orderBy('name')->get(),
            'paymentMethods' => App\Models\PaymentMethod::orderBy('name')->get(),
            'materials' => Material::orderBy('name')->get(),
            'companyExpenseTypes' => CompanyExpenseType::orderBy('name')->get(),
            'internetPackages' => \App\Models\InternetPackage::orderBy('name')->get(),
            'areas' => \App\Models\Area::orderBy('name')->get(),
            'settings' => $settings
        ]);
    })->name('master-data.index');

    Route::post('/master-data/settings', function (Request $request) {
        $data = $request->validate([
            'global_due_date' => 'nullable|integer|min:1|max:31',
            'global_due_time' => 'nullable|date_format:H:i',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'commission_sales_booking' => 'nullable|numeric|min:0',
            'commission_sales_monthly' => 'nullable|numeric|min:0',
            'commission_upline_1_monthly' => 'nullable|numeric|min:0',
            'commission_upline_2_monthly' => 'nullable|numeric|min:0',
            'global_installation_fee' => 'nullable|numeric|min:0',
        ]);

        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('logos', 'public');
            $data['app_logo'] = '/storage/' . $path;
        }

        foreach ($data as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    })->name('master-data.settings.update');

    Route::post('/master-data/company-expense-types', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        CompanyExpenseType::create($data);
        return back()->with('success', 'Jenis Pengeluaran berhasil ditambahkan.');
    })->name('company-expense-types.store');

    Route::delete('/master-data/company-expense-types/{type}', function (CompanyExpenseType $type) {
        $type->delete();
        return back()->with('success', 'Jenis Pengeluaran dihapus.');
    })->name('company-expense-types.destroy');

    Route::post('/master-data/materials', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        Material::create($data);
        return back()->with('success', 'Material berhasil ditambahkan.');
    })->name('materials.store');

    Route::delete('/master-data/materials/{material}', function (Material $material) {
        $material->delete();
        return back()->with('success', 'Material dihapus.');
    })->name('materials.destroy');

    Route::post('/master-data/expense-categories', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        App\Models\ExpenseCategory::create($data);
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    })->name('expense-categories.store');

    Route::delete('/master-data/expense-categories/{category}', function (Request $request, App\Models\ExpenseCategory $category) {
        if (!$request->user()->can('akses_master_data')) abort(403);
        $category->delete();
        return back()->with('success', 'Kategori dihapus.');
    })->name('expense-categories.destroy');

    Route::post('/master-data/internet-packages', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);
        \App\Models\InternetPackage::create($data);
        return back()->with('success', 'Paket Internet berhasil ditambahkan.');
    })->name('internet-packages.store');

    Route::post('/master-data/income-categories', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        App\Models\IncomeCategory::create($data);
        return back()->with('success', 'Kategori Pemasukan berhasil ditambahkan.');
    })->name('income-categories.store');

    Route::delete('/master-data/income-categories/{category}', function (Request $request, App\Models\IncomeCategory $category) {
        if (!$request->user()->can('akses_master_data')) abort(403);
        $category->delete();
        return back()->with('success', 'Kategori Pemasukan dihapus.');
    })->name('income-categories.destroy');

    Route::delete('/master-data/internet-packages/{package}', function (\App\Models\InternetPackage $package) {
        $package->delete();
        return back()->with('success', 'Paket Internet dihapus.');
    })->name('internet-packages.destroy');

    Route::put('/master-data/internet-packages/{package}', function (Request $request, \App\Models\InternetPackage $package) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);
        $package->update($data);
        return back()->with('success', 'Paket Internet berhasil diperbarui.');
    })->name('internet-packages.update');

    Route::post('/master-data/areas', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);
        \App\Models\Area::create($data);
        return back()->with('success', 'Area berhasil ditambahkan.');
    })->name('areas.store');

    Route::put('/master-data/areas/{area}', function (Request $request, \App\Models\Area $area) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);
        $area->update($data);
        return back()->with('success', 'Area berhasil diperbarui.');
    })->name('areas.update');

    Route::delete('/master-data/areas/{area}', function (\App\Models\Area $area) {
        $area->delete();
        return back()->with('success', 'Area dihapus.');
    })->name('areas.destroy');

    Route::get('/backup/restore/{file}', function ($file) {
        $path = storage_path('app/backups/' . $file);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->download($path);
    })->name('backup.restore');

    // Notifications API
    Route::get('/api/notifications', function (Request $request) {
        return response()->json([
            'notifications' => $request->user()->unreadNotifications
        ]);
    })->name('api.notifications.index');

    Route::post('/api/notifications/{id}/read', function (Request $request, $id) {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['success' => true]);
    })->name('api.notifications.read');

    // BILLING DATA (Pindah dari /pelanggan sebelumnya)
    Route::get('/billing', function () {
        \App\Models\Customer::syncBilling();
        $excludedAreas = ['Gratis BC 1', 'Gratis BC 2', 'Gratis BC 3'];
        
        $customers = Customer::where(function($query) {
                $query->whereNull('status_pelanggan')
                      ->orWhereIn('status_pelanggan', ['Aktif', '']);
            })
            ->where(function($query) use ($excludedAreas) {
                $query->whereNotIn('area', $excludedAreas)
                      ->orWhereNull('area');
            })
            ->orderByRaw('COALESCE(register_date, created_at) DESC')
            ->get();
        $paymentMethods = PaymentMethod::orderBy('name')->get();
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Billing', [
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
            'internetPackages' => \App\Models\InternetPackage::orderBy('name')->get(),
            'upgradeHistories' => \App\Models\UpgradeHistory::with('customer')->orderBy('created_at', 'desc')->get(),
            'settings' => $settings,
            'areas' => \App\Models\Area::orderBy('name')->pluck('name'),
        ]);
    })->name('billing.index');

    Route::post('/billing/import', function (Request $request) {
        $data = $request->validate([
            'customersData' => 'required|array'
        ]);
        $packages = \App\Models\InternetPackage::all()->pluck('price', 'name')->toArray();
        $packagesLower = array_change_key_case($packages, CASE_LOWER);

        // Helper function for robust date parsing
        $parseExcelDate = function($dateStr) {
            if (empty($dateStr)) return null;
            $dateStr = trim($dateStr);
            
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
            
            $formats = ['d/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y'];
            foreach ($formats as $format) {
                try {
                    $parsed = \Carbon\Carbon::createFromFormat($format, $dateStr);
                    if ($parsed) return $parsed->format('Y-m-d');
                } catch (\Exception $e) {}
            }
            
            try {
                return \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        foreach ($data['customersData'] as $row) {
            $amount = $row['amount'] ?? 0;
            $paketName = $row['paket'] ?? null;

            if ($paketName && array_key_exists(strtolower($paketName), $packagesLower)) {
                $amount = $packagesLower[strtolower($paketName)];
            }

            $parsedRegisterDate = !empty($row['register_date']) ? $parseExcelDate($row['register_date']) : null;
            $prorataAmount = Customer::calculateProrata($amount, $parsedRegisterDate);

            Customer::create([
                'name' => $row['name'],
                'amount' => $amount,
                'base_amount' => $amount,
                'area' => $row['area'] ?? null,
                'alamat' => $row['alamat'] ?? null,
                'paket' => $paketName,
                'register_date' => $parsedRegisterDate,
                'status_pelanggan' => $row['status_pelanggan'] ?? 'Aktif',
                'status' => 'pending',
                'prorata_amount' => $prorataAmount,
                'last_paid_date' => !empty($row['last_paid_date']) ? $parseExcelDate($row['last_paid_date']) : null,
            ]);
        }
        return back()->with('success', 'Data pelanggan berhasil diimport.');
    })->name('billing.import');

    Route::post('/billing/mass-delete', function (Request $request) {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        }
        Customer::whereIn('id', $ids)->delete();
        return back()->with('success', count($ids) . ' pelanggan berhasil dihapus.');
    })->name('billing.mass-delete');

    Route::post('/billing/{customer}/lunas', function (Request $request, Customer $customer) {
        if ($customer->status === 'paid') {
            return back()->with('error', 'Pelanggan sudah lunas.');
        }

        $paymentMethod = $request->input('payment_method', 'Tunai');
        $paymentDate = $request->input('payment_date', now()->toDateString());
        $paymentAmount = (float) $request->input('payment_amount', 0);
        $isJanjiBayar = $request->boolean('is_janji_bayar', false);
        $promiseDate = $request->input('promise_date', null);
        $keterangan = $request->input('keterangan', null);
        $hasDiskon = $request->boolean('has_diskon', false);
        $diskon = $hasDiskon ? (float) $request->input('diskon', 0) : 0;
        
        $totalTagihan = $customer->amount > 0 ? $customer->amount : $customer->base_amount;
        
        if ($paymentAmount <= 0) {
            return back()->with('error', 'Nominal pembayaran harus lebih dari 0.');
        }

        DB::transaction(function () use ($customer, $paymentMethod, $paymentDate, $paymentAmount, $totalTagihan, $isJanjiBayar, $promiseDate, $keterangan, $diskon) {
            $desc = 'Pembayaran dari ' . $customer->name;
            if ($diskon > 0) {
                $desc .= ' (Diskon: Rp ' . number_format($diskon, 0, ',', '.') . ')';
            }
            if ($keterangan) {
                $desc .= ' - ' . $keterangan;
            }

            // Create transaction record for the payment
            $transaction = Transaction::create([
                'type' => 'income',
                'date' => $paymentDate,
                'description' => $desc,
                'amount' => $paymentAmount,
                'area' => $customer->area,
                'payment_method' => $paymentMethod
            ]);

            $remaining = $totalTagihan - $paymentAmount - $diskon;

            if ($remaining <= 0) {
                // Full payment - mark as paid
                $customer->update([
                    'status' => 'paid',
                    'amount' => 0,
                    'last_paid_date' => $paymentDate,
                    'last_paid_by' => auth()->user() ? auth()->user()->name : 'Admin',
                    'promise_date' => null,
                    'prorata_amount' => null
                ]);

                // Generate Monthly Commissions if customer has sales and is active
                if ($customer->sales_id && strtolower($customer->status_pelanggan ?? 'aktif') === 'aktif') {
                    $sales = \App\Models\Sales::find($customer->sales_id);
                    if ($sales) {
                        $settings = \App\Models\Setting::pluck('value', 'key');
                        
                        // Direct Sales Commission
                        $monthlyDirect = $settings['commission_sales_monthly'] ?? 0;
                        if ($monthlyDirect > 0) {
                            \App\Models\Commission::create([
                                'sales_id' => $sales->id,
                                'customer_id' => $customer->id,
                                'transaction_id' => $transaction->id,
                                'type' => 'monthly_direct',
                                'amount' => $monthlyDirect,
                                'status' => 'pending',
                                'description' => 'Komisi Bulanan (Sales): ' . $customer->name,
                            ]);
                        }

                        // Upline 1 Commission
                        if ($sales->parent_id) {
                            $upline1 = \App\Models\Sales::find($sales->parent_id);
                            $monthlyUpline1 = $settings['commission_upline_1_monthly'] ?? 0;
                            if ($upline1 && $monthlyUpline1 > 0) {
                                \App\Models\Commission::create([
                                    'sales_id' => $upline1->id,
                                    'customer_id' => $customer->id,
                                    'transaction_id' => $transaction->id,
                                    'type' => 'monthly_upline_1',
                                    'amount' => $monthlyUpline1,
                                    'status' => 'pending',
                                    'description' => 'Komisi Bulanan (Upline 1): ' . $customer->name,
                                ]);
                            }

                            // Upline 2 Commission
                            if ($upline1 && $upline1->parent_id) {
                                $upline2 = \App\Models\Sales::find($upline1->parent_id);
                                $monthlyUpline2 = $settings['commission_upline_2_monthly'] ?? 0;
                                if ($upline2 && $monthlyUpline2 > 0) {
                                    \App\Models\Commission::create([
                                        'sales_id' => $upline2->id,
                                        'customer_id' => $customer->id,
                                        'transaction_id' => $transaction->id,
                                        'type' => 'monthly_upline_2',
                                        'amount' => $monthlyUpline2,
                                        'status' => 'pending',
                                        'description' => 'Komisi Bulanan (Upline 2): ' . $customer->name,
                                    ]);
                                }
                            }
                        }

                        // RELEASE HOLD BOOKING COMMISSIONS
                        \App\Models\Commission::where('customer_id', $customer->id)
                            ->where('type', 'booking')
                            ->where('status', 'hold')
                            ->update(['status' => 'pending']);
                    }
                }
            } else {
                // Partial payment - update remaining amount, keep as pending/nunggak
                $customer->update([
                    'amount' => $remaining,
                    'last_paid_by' => auth()->user() ? auth()->user()->name : 'Admin',
                    'promise_date' => $isJanjiBayar ? $promiseDate : $customer->promise_date
                ]);
            }
        });

        $remaining = $totalTagihan - $paymentAmount - $diskon;
        if ($remaining <= 0) {
            return back()->with('success', 'Pelanggan berhasil ditandai lunas.');
        } else {
            return back()->with('success', 'Pembayaran sebagian berhasil. Sisa tagihan: Rp ' . number_format($remaining, 0, ',', '.'));
        }
    })->name('billing.lunas');
    
    Route::post('/billing/{customer}/janji-bayar', function (Request $request, Customer $customer) {
        $data = $request->validate([
            'promise_date' => 'required|date'
        ]);

        $customer->update([
            'promise_date' => $data['promise_date']
        ]);

        return back()->with('success', 'Tanggal janji bayar berhasil diatur.');
    })->name('billing.janji-bayar');

    Route::post('/billing/{customer}/batal-janji', function (Request $request, Customer $customer) {
        $customer->update(['promise_date' => null]);
        return back()->with('success', 'Janji bayar berhasil dibatalkan.');
    })->name('billing.batal-janji');
    
    Route::post('/billing/{customer}/rollback-lunas', function (Request $request, Customer $customer) {
        if ($customer->status !== 'paid') {
            return back()->with('error', 'Pelanggan belum lunas.');
        }

        DB::transaction(function () use ($customer) {
            // Find the latest transaction for this customer
            $latestTransaction = Transaction::where('description', 'Pembayaran dari ' . $customer->name)
                ->where('type', 'income')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestTransaction) {
                $latestTransaction->delete();
            }

            // Find the next latest transaction to rollback last_paid_date
            $previousTransaction = Transaction::where('description', 'Pembayaran dari ' . $customer->name)
                ->where('type', 'income')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($previousTransaction) {
                $customer->update([
                    'last_paid_date' => $previousTransaction->date,
                    'last_paid_by' => null,
                ]);
            } else {
                $customer->update([
                    'last_paid_date' => null,
                    'last_paid_by' => null,
                ]);
            }
            
            // Set them to pending, the syncBilling will recalculate if needed when page loads
            $customer->update(['status' => 'pending', 'amount' => $customer->base_amount]);
        });

        return back()->with('success', 'Pelunasan berhasil dibatalkan (rollback).');
    })->name('billing.rollback');
    
    // PELANGGAN INAKTIF (Berhenti / Stop / Gratis)
    Route::get('/pelanggan-inaktif', function () {
        \App\Models\Customer::syncBilling();
        $excludedAreas = ['Gratis BC 1', 'Gratis BC 2', 'Gratis BC 3'];
        
        $customers = Customer::with('suspensions')
            ->where(function($query) use ($excludedAreas) {
                $query->whereIn('status_pelanggan', ['Berhenti', 'Nonaktif', 'Suspend', 'Isolir', 'Gratis', 'Stop Permanen', 'Berhenti sementara'])
                      ->orWhereIn('area', $excludedAreas);
            })
            ->orderBy('created_at', 'desc')
            ->get();
            
        $allSuspensions = \App\Models\CustomerSuspension::with('customer')
            ->orderBy('suspend_start_date', 'desc')
            ->get();
            
        return Inertia::render('PelangganInaktif', [
            'customers' => $customers,
            'allSuspensionsData' => $allSuspensions,
            'areas' => \App\Models\Area::orderBy('name')->pluck('name')
        ]);
    })->name('pelanggan.inaktif');

    // PELANGGAN PANTAUAN (Suspend, Nunggak, Janji Bayar, Berhenti < 4 bln)
    Route::get('/pelanggan-pantauan', function () {
        \App\Models\Customer::syncBilling();
        
        $customersQuery = Customer::withCount('suspensions')
            ->where(function ($query) {
                $query->whereIn('status_pelanggan', ['Suspend', 'Berhenti sementara', 'Isolir', 'Berhenti', 'Nonaktif', 'Stop Permanen', 'Putus'])
                      ->orWhere('status', 'nunggak')
                      ->orWhereNotNull('promise_date');
            })
            ->orderBy('created_at', 'desc')
            ->get();
            
        $customers = $customersQuery->filter(function($c) {
            $isBerhenti = in_array(strtolower($c->status_pelanggan ?? ''), ['berhenti', 'nonaktif', 'stop permanen', 'putus']);
            if ($isBerhenti) {
                $start = $c->register_date ? \Carbon\Carbon::parse($c->register_date) : \Carbon\Carbon::parse($c->created_at);
                $end = $c->stop_date ? \Carbon\Carbon::parse($c->stop_date) : \Carbon\Carbon::parse($c->updated_at);
                // Return true only if duration is less than 4 months
                return $start->diffInMonths($end) < 4;
            }
            // For other conditions (suspend, nunggak, janji bayar)
            return true;
        })->values();
            
        return Inertia::render('PelangganPantauan', [
            'customers' => $customers,
        ]);
    })->name('pelanggan.pantauan');

    // BOOKING PELANGGAN
    Route::get('/booking', function () {
        $user = auth()->user();
        $query = Customer::where('status_pelanggan', 'Booking');
        
        if ($user && $user->hasRole('sales')) {
            $mySalesProfile = \App\Models\Sales::where('user_id', $user->id)->first();
            if ($mySalesProfile) {
                $query->where('sales_id', $mySalesProfile->id);
            } else {
                $query->where('id', -1);
            }
        }

        $customers = $query->orderBy('created_at', 'desc')->get();
        return Inertia::render('Booking', [
            'customers' => $customers->load('sales'),
            'sales' => \App\Models\Sales::orderBy('name')->get(),
            'areas' => \App\Models\Area::orderBy('name')->get(),
            'pakets' => \App\Models\InternetPackage::orderBy('name')->get(),
            'globalInstallationFee' => \App\Models\Setting::where('key', 'global_installation_fee')->value('value') ?? 0,
            'is_sales' => $user && $user->hasRole('sales'),
            'can_activate' => $user && ($user->hasRole('admin') || $user->can('setujui_booking')),
        ]);
    })->name('booking.index');

    Route::post('/booking', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'base_amount' => 'required|numeric|min:0',
            'area' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kecamatan' => 'nullable|string|max:255',
            'desa_kelurahan' => 'nullable|string|max:255',
            'rt_rw' => 'nullable|string|max:50',
            'paket' => 'nullable|string|max:255',
            'register_date' => 'nullable|date',
            'due_date' => 'nullable|integer|min:1|max:31',
            'sales_id' => 'nullable|exists:sales,id',
            'coordinate' => 'nullable|string',
            'no_wa' => 'nullable|string|max:50',
            'foto_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);
        
        $user = auth()->user();
        if ($user && $user->hasRole('sales')) {
            $mySalesProfile = \App\Models\Sales::where('user_id', $user->id)->first();
            if ($mySalesProfile) {
                $data['sales_id'] = $mySalesProfile->id;
            }
        }
        
        if ($request->hasFile('foto_ktp')) {
            $data['foto_ktp'] = $request->file('foto_ktp')->store('ktp', 'public');
        }

        $data['status_pelanggan'] = 'Booking';
        $data['amount'] = 0;
        $data['status'] = 'pending';
        Customer::create($data);

        return back()->with('success', 'Data booking berhasil ditambahkan.');
    })->name('booking.store');

    Route::post('/booking/{customer}/activate', function (Request $request, Customer $customer) {
        if (!$request->user()->hasRole('admin') && !$request->user()->can('setujui_booking')) {
            abort(403);
        }

        if ($customer->status_pelanggan !== 'Booking' && $customer->status_pelanggan !== 'Proses') {
            return back()->with('error', 'Hanya pelanggan dengan status Booking atau Proses yang dapat diaktivasi.');
        }

        $customer->update([
            'status_pelanggan' => 'Aktif',
            'register_date' => now()->format('Y-m-d'),
            'keterangan_status' => null,
        ]);

        // Generate Booking Commission if sales_id is present
        if ($customer->sales_id) {
            $bookingCommission = \App\Models\Setting::where('key', 'commission_sales_booking')->value('value');
            if ($bookingCommission && $bookingCommission > 0) {
                \App\Models\Commission::create([
                    'sales_id' => $customer->sales_id,
                    'customer_id' => $customer->id,
                    'type' => 'booking',
                    'amount' => $bookingCommission,
                    'status' => 'hold',
                    'description' => 'Komisi Booking Pelanggan: ' . $customer->name,
                ]);
            }
            
            // Notify Sales
            $salesUser = \App\Models\Sales::find($customer->sales_id)?->user;
            if ($salesUser) {
                $salesUser->notify(new \App\Notifications\BookingStatusNotification($customer->name, 'Aktif'));
            }
        }

        return back()->with('success', 'Booking berhasil diaktivasi menjadi Pelanggan Aktif.');
    })->name('booking.activate');

    Route::post('/booking/{customer}/status', function (Request $request, Customer $customer) {
        if (!$request->user()->hasRole('admin') && !$request->user()->can('setujui_booking')) {
            abort(403);
        }

        $data = $request->validate([
            'status' => 'required|in:Proses,Batal',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $customer->update([
            'status_pelanggan' => $data['status'],
            'keterangan_status' => $data['keterangan'] ?? null,
        ]);

        if ($customer->sales_id) {
            $salesUser = \App\Models\Sales::find($customer->sales_id)?->user;
            if ($salesUser) {
                $salesUser->notify(new \App\Notifications\BookingStatusNotification($customer->name, $data['status'], $data['keterangan']));
            }
        }

        return back()->with('success', 'Status booking berhasil diubah.');
    })->name('booking.status');

    Route::delete('/booking/{customer}', function (Request $request, Customer $customer) {
        if (!$request->user()->can('hapus_pelanggan')) abort(403);
        $customer->delete();
        return back()->with('success', 'Booking dihapus.');
    })->name('booking.destroy');
    
    // MASTER DATA PELANGGAN
    Route::get('/pelanggan', function () {
        // Halaman ini khusus untuk Master Data Pelanggan (CRUD basic)
        $customers = Customer::where('status_pelanggan', '!=', 'Booking')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Generate Chart Data (Last 6 months)
        $chartLabels = [];
        $chartRegistrations = [];
        $chartStops = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $chartLabels[] = $month->translatedFormat('M Y');
            
            $chartRegistrations[] = Customer::whereYear('register_date', $month->year)
                ->whereMonth('register_date', $month->month)
                ->count();
                
            $chartStops[] = Customer::whereYear('stop_date', $month->year)
                ->whereMonth('stop_date', $month->month)
                ->count();
        }

        return Inertia::render('Pelanggan', [
            'customers' => $customers->load('sales'),
            'sales' => \App\Models\Sales::orderBy('name')->get(),
            'chart' => [
                'labels' => $chartLabels,
                'registrations' => $chartRegistrations,
                'stops' => $chartStops
            ],
            'areas' => \App\Models\Area::orderBy('name')->get(),
            'pakets' => \App\Models\InternetPackage::orderBy('name')->get(),
            'globalInstallationFee' => \App\Models\Setting::where('key', 'global_installation_fee')->value('value') ?? 0,
        ]);
    })->name('pelanggan.index');

    Route::post('/pelanggan', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'base_amount' => 'required|numeric|min:0',
            'area' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kecamatan' => 'nullable|string|max:255',
            'desa_kelurahan' => 'nullable|string|max:255',
            'rt_rw' => 'nullable|string|max:50',
            'paket' => 'nullable|string|max:255',
            'register_date' => 'nullable|date',
            'status_pelanggan' => 'nullable|string|max:255',
            'due_date' => 'nullable|integer|min:1|max:31',
            'sales_id' => 'nullable|exists:sales,id',
            'coordinate' => 'nullable|string',
            'no_wa' => 'nullable|string|max:50',
            'foto_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);
        
        if ($request->hasFile('foto_ktp')) {
            $data['foto_ktp'] = $request->file('foto_ktp')->store('ktp', 'public');
        }

        $data['amount'] = 0; // Default amount
        $data['status'] = 'pending';
        $data['prorata_amount'] = Customer::calculateProrata($data['base_amount'], $data['register_date'] ?? null);
        
        Customer::create($data);
        return back()->with('success', 'Data pelanggan baru berhasil ditambahkan.');
    })->name('pelanggan.store');

    Route::post('/pelanggan/import', function (Request $request) {
        $data = $request->validate([
            'customersData' => 'required|array'
        ]);
        
        $packages = \App\Models\InternetPackage::all()->pluck('price', 'name')->toArray();
        $packagesLower = array_change_key_case($packages, CASE_LOWER);

        foreach ($data['customersData'] as $row) {
            $paketName = $row['paket'] ?? null;
            $baseAmount = $row['base_amount'] ?? 0;

            if ($paketName && array_key_exists(strtolower($paketName), $packagesLower)) {
                $baseAmount = $packagesLower[strtolower($paketName)];
            }

            $parsedRegisterDate = !empty($row['register_date']) ? date('Y-m-d', strtotime($row['register_date'])) : null;
            $prorataAmount = Customer::calculateProrata($baseAmount, $parsedRegisterDate);

            Customer::updateOrCreate(
                ['name' => $row['name']],
                [
                    'base_amount' => $baseAmount,
                    'area' => $row['area'] ?? null,
                    'alamat' => $row['alamat'] ?? null,
                    'paket' => $paketName,
                    'register_date' => $parsedRegisterDate,
                    'status_pelanggan' => $row['status_pelanggan'] ?? 'Aktif',
                    'no_wa' => $row['no_wa'] ?? null,
                    'status' => 'pending',
                    'prorata_amount' => $prorataAmount,
                ]
            );
        }
        return back()->with('success', 'Data pelanggan berhasil diimport.');
    })->name('pelanggan.import');

    Route::post('/pelanggan/mass-delete', function (Request $request) {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        }
        Customer::whereIn('id', $ids)->delete();
        return back()->with('success', count($ids) . ' pelanggan berhasil dihapus.');
    })->name('pelanggan.mass-delete');

    Route::delete('/pelanggan/{customer}', function (Request $request, Customer $customer) {
        if (!$request->user()->can('hapus_pelanggan')) abort(403);
        $customer->delete();
        return back()->with('success', 'Pelanggan dihapus.');
    })->name('pelanggan.destroy');

    Route::put('/pelanggan/{customer}', function (Request $request, Customer $customer) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kecamatan' => 'nullable|string|max:255',
            'desa_kelurahan' => 'nullable|string|max:255',
            'rt_rw' => 'nullable|string|max:50',
            'paket' => 'nullable|string|max:255',
            'register_date' => 'nullable|date',
            'status_pelanggan' => 'nullable|string|max:255',
            'due_date' => 'nullable|integer|min:1|max:31',
            'suspend_start_date' => 'nullable|date',
            'suspend_end_date' => 'nullable|date',
            'stop_date' => 'nullable|date',
            'last_paid_date' => 'nullable|date',
            'base_amount' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'prorata_amount' => 'nullable|numeric|min:0',
            'is_upgrade' => 'nullable|boolean',
            'sales_id' => 'nullable|exists:sales,id',
            'coordinate' => 'nullable|string',
            'no_wa' => 'nullable|string|max:50',
            'foto_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);

        $originalStatus = $customer->status_pelanggan;
        $originalPaket = $customer->paket;
        
        if (!isset($data['prorata_amount'])) {
            $data['prorata_amount'] = Customer::calculateProrata($data['base_amount'] ?? $customer->base_amount, $data['register_date'] ?? clone $customer->register_date);
        }

        $isUpgrade = filter_var($request->input('is_upgrade'), FILTER_VALIDATE_BOOLEAN);
        unset($data['is_upgrade']);
        
        if ($request->hasFile('foto_ktp')) {
            // Delete old photo if needed, optional
            // if ($customer->foto_ktp) { \Illuminate\Support\Facades\Storage::disk('public')->delete($customer->foto_ktp); }
            $data['foto_ktp'] = $request->file('foto_ktp')->store('ktp', 'public');
        }
        
        $customer->update($data);

        // CLAWBACK LOGIC (Pemotongan komisi jika berhenti < 3 bulan)
        $inactiveStatuses = ['berhenti', 'stop permanen', 'nonaktif'];
        if (!in_array(strtolower($originalStatus ?? ''), $inactiveStatuses) && 
            in_array(strtolower($data['status_pelanggan'] ?? ''), $inactiveStatuses)) {
            
            if ($customer->register_date) {
                $regDate = \Carbon\Carbon::parse($customer->register_date);
                if ($regDate->diffInMonths(now()) < 3) {
                    $paidCommissions = \App\Models\Commission::where('customer_id', $customer->id)
                        ->where('status', 'paid')
                        ->get();
                    
                    $clawbacks = [];
                    foreach ($paidCommissions as $pc) {
                        if (!isset($clawbacks[$pc->sales_id])) {
                            $clawbacks[$pc->sales_id] = 0;
                        }
                        $clawbacks[$pc->sales_id] += $pc->amount;
                    }

                    foreach ($clawbacks as $salesId => $totalAmount) {
                        if ($totalAmount > 0) {
                            \App\Models\Commission::create([
                                'sales_id' => $salesId,
                                'customer_id' => $customer->id,
                                'type' => 'clawback',
                                'amount' => -$totalAmount,
                                'status' => 'pending', // Biarkan pending agar terakumulasi dan memotong saldo saat direkap
                                'description' => 'Potongan komisi (Berhenti < 3 bln) - Pelanggan: ' . $customer->name
                            ]);
                        }
                    }

                    // Void (batalkan) komisi yang belum sempat dibayar
                    \App\Models\Commission::where('customer_id', $customer->id)
                        ->whereIn('status', ['pending', 'hold'])
                        ->update(['status' => 'void']);
                }
            }
        }

        // Jika toggle upgrade diaktifkan, catat riwayat upgrade (bahkan jika nama paket tidak berubah, mungkin harganya yang berubah)
        if ($isUpgrade) {
            \App\Models\UpgradeHistory::create([
                'customer_id' => $customer->id,
                'old_paket' => $originalPaket,
                'new_paket' => $data['paket'] ?? $originalPaket,
            ]);
        }

        // Jika berubah dari Suspend ke Aktif
        if (in_array(strtolower($originalStatus ?? ''), ['suspend', 'berhenti sementara']) && strtolower($data['status_pelanggan'] ?? '') === 'aktif') {
            // Catat pengaktifan ini sebagai suspend_end_date di riwayat
            \App\Models\CustomerSuspension::where('customer_id', $customer->id)
                ->where('suspend_start_date', $data['suspend_start_date'] ?? $customer->suspend_start_date)
                ->update(['suspend_end_date' => $data['suspend_end_date'] ?? date('Y-m-d')]);
            
            // Bersihkan tanggal suspend di record customer karena sudah aktif kembali
            $customer->update([
                'suspend_start_date' => null,
                'suspend_end_date' => null
            ]);
        }
        // Jika statusnya tetap atau menjadi Suspend
        elseif (in_array(strtolower($data['status_pelanggan'] ?? ''), ['suspend', 'berhenti sementara']) && !empty($data['suspend_start_date'])) {
            \App\Models\CustomerSuspension::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'suspend_start_date' => $data['suspend_start_date']
                ],
                [
                    'suspend_end_date' => $data['suspend_end_date'] ?? null
                ]
            );
        }

        return back()->with('success', 'Data pelanggan berhasil diperbarui.');
    })->name('pelanggan.update');

    // VOUCHER & SALDO
    Route::get('/voucher-saldo', function (Request $request) {
        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-t'));
        $status = $request->query('status', 'Semua'); // 'Semua', 'Lunas', 'Piutang'
        $area = $request->query('area', 'Semua');

        $query = Transaction::with('reseller')
            ->withSum('children', 'amount')
            ->where('type', 'income')
            ->whereIn('income_source', ['voucher', 'saldo'])
            ->whereBetween('date', [$startDate, $endDate]);
            
        if ($status === 'Lunas') {
            $query->where('payment_status', 'paid');
        } elseif ($status === 'Piutang') {
            $query->where('payment_status', 'unpaid');
        }

        if ($area !== 'Semua' && $area !== '') {
            $query->where('area', $area);
        }
            
        $transactions = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
            
        $paymentMethods = PaymentMethod::orderBy('name')->get();
        
        $resellers = Reseller::with(['transactions' => function($q) {
            $q->where('transaction_mode', 'Piutang')
              ->where('payment_status', 'unpaid')
              ->withSum('children', 'amount');
        }])->orderBy('name')->get();

        $resellers->transform(function($reseller) {
            $totalPiutang = $reseller->transactions->sum(function($t) {
                return $t->amount - ($t->children_sum_amount ?? 0);
            });
            $reseller->total_piutang = $totalPiutang;
            unset($reseller->transactions);
            return $reseller;
        });

        $areas = \App\Models\Area::orderBy('name')->pluck('name');

        return Inertia::render('VoucherSaldo', [
            'transactions' => $transactions,
            'paymentMethods' => $paymentMethods,
            'resellers' => $resellers,
            'areas' => $areas,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'area' => $area,
            ]
        ]);
    })->name('voucher-saldo.index');

    Route::post('/voucher-saldo', function (Request $request) {
        $data = $request->validate([
            'income_source' => 'required|in:voucher,saldo',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'transaction_mode' => 'required|in:Tunai,Piutang',
            'reseller_id' => 'nullable|exists:resellers,id',
            'area' => 'nullable|string',
        ]);

        $paymentStatus = $data['transaction_mode'] === 'Piutang' ? 'unpaid' : 'paid';
        $paidAt = $paymentStatus === 'paid' ? $data['date'] : null;

        Transaction::create([
            'type' => 'income',
            'income_source' => $data['income_source'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'date' => $data['date'],
            'payment_method' => $data['payment_method'] ?? 'Tunai',
            'transaction_mode' => $data['transaction_mode'],
            'reseller_id' => $data['reseller_id'] ?? null,
            'area' => $data['area'] ?? null,
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt,
        ]);

        return back()->with('success', 'Transaksi berhasil ditambahkan.');
    })->name('voucher-saldo.store');

    Route::delete('/voucher-saldo/{transaction}', function (Transaction $transaction) {
        if (!in_array($transaction->income_source, ['voucher', 'saldo'])) {
            return back()->with('error', 'Transaksi bukan berupa voucher atau saldo.');
        }
        
        $parentId = $transaction->parent_id;
        $transaction->delete();
        
        // If it was a payment for a piutang, check parent's status
        if ($parentId) {
            $parent = Transaction::find($parentId);
            if ($parent) {
                $totalPaid = Transaction::where('parent_id', $parentId)->sum('amount');
                if ($totalPaid < $parent->amount) {
                    $parent->update([
                        'payment_status' => 'unpaid',
                        'paid_at' => null
                    ]);
                }
            }
        }
        
        return back()->with('success', 'Transaksi berhasil dihapus.');
    })->name('voucher-saldo.destroy');

    Route::post('/voucher-saldo/{transaction}/lunas', function (Request $request, Transaction $transaction) {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method' => 'required|string',
            'collector' => 'nullable|string',
            'proof' => 'nullable|image|max:2048',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payments', 'public');
        }

        // Create child transaction (the payment)
        Transaction::create([
            'type' => 'income',
            'amount' => $data['amount'],
            'date' => $data['date'],
            'payment_method' => $data['payment_method'],
            'transaction_mode' => 'Tunai',
            'income_source' => $transaction->income_source,
            'description' => 'Pembayaran piutang: ' . $transaction->description,
            'parent_id' => $transaction->id,
            'collector' => $data['collector'],
            'proof' => $proofPath,
            'payment_status' => 'paid',
            'paid_at' => $data['date'],
            'reseller_id' => $transaction->reseller_id,
        ]);

        // Check if fully paid
        $totalPaid = Transaction::where('parent_id', $transaction->id)->sum('amount');
        if ($totalPaid >= $transaction->amount) {
            $transaction->update([
                'payment_status' => 'paid',
                'paid_at' => date('Y-m-d')
            ]);
        }

        return back()->with('success', 'Pembayaran piutang berhasil dicatat.');
    })->name('voucher-saldo.lunas');

    Route::post('/voucher-saldo/{transaction}/batal-lunas', function (Transaction $transaction) {
        if ($transaction->transaction_mode !== 'Piutang') {
            return back()->with('error', 'Hanya transaksi piutang yang dapat dibatalkan.');
        }

        // Delete all child payments
        Transaction::where('parent_id', $transaction->id)->delete();

        // Revert parent status
        $transaction->update([
            'payment_status' => 'unpaid',
            'paid_at' => null
        ]);

        return back()->with('success', 'Status lunas berhasil dibatalkan.');
    })->name('voucher-saldo.batal-lunas');

    // RESELLER CRUD
    Route::post('/reseller', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);
        Reseller::create($data);
        return back()->with('success', 'Data reseller berhasil ditambahkan.');
    })->name('reseller.store');

    Route::put('/reseller/{reseller}', function (Request $request, Reseller $reseller) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);
        $reseller->update($data);
        return back()->with('success', 'Data reseller berhasil diperbarui.');
    })->name('reseller.update');

    Route::delete('/reseller/{reseller}', function (Reseller $reseller) {
        $reseller->delete();
        return back()->with('success', 'Reseller dihapus.');
    })->name('reseller.destroy');

    Route::post('/reseller/import', function (Request $request) {
        $data = $request->validate([
            'resellersData' => 'required|array'
        ]);
        
        foreach ($data['resellersData'] as $row) {
            Reseller::create([
                'name' => $row['name'],
                'area' => $row['area'] ?? null,
                'alamat' => $row['alamat'] ?? null,
                'phone' => $row['phone'] ?? null,
            ]);
        }
        return back()->with('success', 'Data reseller berhasil diimport.');
    })->name('reseller.import');

    // LAPORAN
    Route::get('/laporan', function (Request $request) {
        \App\Models\Customer::syncBilling();
        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-t'));
        $areaFilter = $request->query('area', '');
        $kategori = $request->query('kategori', '');
        $cetFilter = $request->query('company_expense_type_id', '');
        $materialFilter = $request->query('material_id', '');
        $paymentMethodFilter = $request->query('payment_method', '');

        // Unpaid stats (filtered by area if applicable)
        $unpaidQuery = Customer::whereIn('status', ['pending', 'nunggak'])
            ->where(function($q) {
                $q->where('status_pelanggan', 'Aktif')
                  ->orWhereNull('status_pelanggan')
                  ->orWhere('status_pelanggan', '');
            });
            
        if (!empty($areaFilter)) {
            $unpaidQuery->where('area', $areaFilter);
        }
        $unpaidCount = $unpaidQuery->count();
        $unpaidTotal = (clone $unpaidQuery)->sum('amount');
        
        $unpaidList = [];
        if ($kategori === 'belum_lunas') {
            $unpaidList = (clone $unpaidQuery)->orderBy('name', 'asc')->get();
        }

        // Transactions (filtered by date, area, and type)
        $query = Transaction::whereBetween('date', [$startDate, $endDate])
            ->where(function($q) {
                $q->where('type', '!=', 'income')
                  ->orWhere(function($q2) {
                      $q2->where('type', 'income')
                         ->where(function($q3) {
                             $q3->where('payment_status', 'paid')
                                ->orWhereNull('payment_status');
                         });
                  });
            });

        if (!empty($areaFilter)) {
            $query->where('area', $areaFilter);
        }
        
        // Calculate totals BEFORE filtering by kategori (or AFTER?)
        // Let's filter transactions if kategori is pemasukan/pengeluaran
        if ($kategori === 'income') {
            $query->where('type', 'income');
        } elseif ($kategori === 'expense') {
            $query->where('type', 'expense');
        } elseif (str_starts_with($kategori, 'cat_')) {
            $catId = str_replace('cat_', '', $kategori);
            $query->where('type', 'expense')->where('expense_category_id', $catId);
        }

        if (!empty($cetFilter)) {
            $query->where('company_expense_type_id', $cetFilter);
        }

        if (!empty($materialFilter)) {
            $query->where('material_id', $materialFilter);
        }

        if (!empty($paymentMethodFilter)) {
            $query->where('payment_method', $paymentMethodFilter);
        }

        $transactions = $query->with(['expenseCategory', 'companyExpenseType', 'material'])->orderBy('date', 'asc')->orderBy('id', 'asc')->get();
        
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        
        $areas = \App\Models\Area::orderBy('name')->pluck('name');
        $expenseCategories = App\Models\ExpenseCategory::orderBy('name')->get();
        $companyExpenseTypes = App\Models\CompanyExpenseType::orderBy('name')->get();
        $materials = App\Models\Material::orderBy('name')->get();
        $paymentMethods = App\Models\PaymentMethod::orderBy('name')->get();

        return Inertia::render('Laporan', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'area' => $areaFilter,
                'kategori' => $kategori,
                'company_expense_type_id' => $cetFilter,
                'material_id' => $materialFilter,
                'payment_method' => $paymentMethodFilter
            ],
            'summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'balance' => $totalIncome - $totalExpense
            ],
            'transactions' => $transactions,
            'areas' => $areas,
            'expenseCategories' => $expenseCategories,
            'companyExpenseTypes' => $companyExpenseTypes,
            'materials' => $materials,
            'paymentMethods' => $paymentMethods,
            'unpaid' => [
                'count' => $unpaidCount,
                'total' => (float)$unpaidTotal,
            ],
            'unpaid_list' => $unpaidList
        ]);
    })->name('laporan');

    // AFFILIATES
    Route::get('/affiliates', function (Request $request) {
        $user = auth()->user();
        
        $affiliatesQuery = \App\Models\Affiliate::with('parent')
            ->withCount('children')
            ->orderBy('name');
            
        $salesQuery = \App\Models\Sales::with('parent', 'user')
            ->withCount('children')
            ->orderBy('name');
            
        // If user is sales, restrict view
        if ($user && $user->hasRole('sales')) {
            $mySalesProfile = \App\Models\Sales::where('user_id', $user->id)->first();
            if ($mySalesProfile) {
                $allSalesForHierarchy = \App\Models\Sales::select('id', 'parent_id')->get();
                $descendantIds = [];
                $getDescendants = function($parentId, $currentDepth = 1) use (&$getDescendants, &$descendantIds, $allSalesForHierarchy) {
                    if ($currentDepth > 2) return; // Limit to Downline 2
                    $children = $allSalesForHierarchy->where('parent_id', $parentId);
                    foreach($children as $child) {
                        $descendantIds[] = $child->id;
                        $getDescendants($child->id, $currentDepth + 1);
                    }
                };
                $getDescendants($mySalesProfile->id, 1);
                
                $allowedIds = array_merge([$mySalesProfile->id], $descendantIds);
                
                $salesQuery->whereIn('id', $allowedIds);
            } else {
                // No profile found, show empty
                $salesQuery->where('id', -1);
            }
        }

        $affiliates = $affiliatesQuery->get();
        $sales = $salesQuery->get();
        
        // Add `is_current_user` flag to identify the upline in the frontend
        if ($user && $user->hasRole('sales')) {
            $mySalesProfileId = \App\Models\Sales::where('user_id', $user->id)->value('id');
            $sales->transform(function ($item) use ($mySalesProfileId) {
                $item->is_current_user = ($item->id === $mySalesProfileId);
                return $item;
            });
        }
        
        $areas = \App\Models\Area::orderBy('name')->pluck('name');
        
        $isSales = $user && $user->hasRole('sales');
        
        return Inertia::render('Affiliate', [
            'affiliates' => $affiliates,
            'sales' => $sales,
            'areas' => \App\Models\Area::orderBy('name')->pluck('name'),
            'is_sales' => $user && $user->hasRole('sales'),
            'commissions' => [
                'sales_monthly' => \App\Models\Setting::where('key', 'commission_sales_monthly')->value('value'),
                'upline_1_monthly' => \App\Models\Setting::where('key', 'commission_upline_1_monthly')->value('value'),
                'upline_2_monthly' => \App\Models\Setting::where('key', 'commission_upline_2_monthly')->value('value'),
            ]
        ]);
    })->name('affiliates.index');

    Route::get('/affiliates/diagram', function (Request $request) {
        $user = auth()->user();
        
        $salesQuery = \App\Models\Sales::with('parent', 'user')->orderBy('name');
            
        // If user is sales, restrict view
        if ($user && $user->hasRole('sales')) {
            $mySalesProfile = \App\Models\Sales::where('user_id', $user->id)->first();
            if ($mySalesProfile) {
                $allSalesForHierarchy = \App\Models\Sales::select('id', 'parent_id')->get();
                $descendantIds = [];
                $getDescendants = function($parentId, $currentDepth = 1) use (&$getDescendants, &$descendantIds, $allSalesForHierarchy) {
                    if ($currentDepth > 2) return; // Limit to Downline 2
                    $children = $allSalesForHierarchy->where('parent_id', $parentId);
                    foreach($children as $child) {
                        $descendantIds[] = $child->id;
                        $getDescendants($child->id, $currentDepth + 1);
                    }
                };
                $getDescendants($mySalesProfile->id, 1);
                
                $allowedIds = array_merge([$mySalesProfile->id], $descendantIds);
                
                $salesQuery->whereIn('id', $allowedIds);
            } else {
                $salesQuery->where('id', -1);
            }
        }

        $sales = $salesQuery->get();
        
        if ($user && $user->hasRole('sales')) {
            $mySalesProfileId = \App\Models\Sales::where('user_id', $user->id)->value('id');
            $sales->transform(function ($item) use ($mySalesProfileId) {
                $item->is_current_user = ($item->id === $mySalesProfileId);
                return $item;
            });
        }
        
        return Inertia::render('AffiliateDiagram', [
            'sales' => $sales,
            'is_sales' => $user && $user->hasRole('sales'),
        ]);
    })->name('affiliates.diagram');

    Route::post('/affiliates', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:affiliates,id',
            'status' => 'required|string|in:Aktif,Nonaktif',
            'commission_rate' => 'nullable|numeric|min:0',
            'bank_account' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
        ]);
        
        $data['commission_rate'] = $data['commission_rate'] ?? 0;
        
        // Auto-generate member_number (e.g. AF-YYYYMMDD-XXXX)
        $latest = \App\Models\Affiliate::orderBy('id', 'desc')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        $data['member_number'] = 'AF-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        
        \App\Models\Affiliate::create($data);
        return back()->with('success', 'Afiliasi berhasil ditambahkan.');
    })->name('affiliates.store');

    Route::put('/affiliates/{affiliate}', function (Request $request, \App\Models\Affiliate $affiliate) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:affiliates,id',
            'status' => 'required|string|in:Aktif,Nonaktif',
            'commission_rate' => 'nullable|numeric|min:0',
            'bank_account' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
        ]);

        if ($data['parent_id'] == $affiliate->id) {
            return back()->with('error', 'Afiliasi tidak bisa menjadi parent untuk dirinya sendiri.');
        }

        $data['commission_rate'] = $data['commission_rate'] ?? 0;
        $affiliate->update($data);
        return back()->with('success', 'Afiliasi berhasil diperbarui.');
    })->name('affiliates.update');

    Route::delete('/affiliates/{affiliate}', function (\App\Models\Affiliate $affiliate) {
        $affiliate->delete();
        return back()->with('success', 'Afiliasi dihapus.');
    })->name('affiliates.destroy');

    // SALES
    Route::post('/sales', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|string|email|max:255',
            'area' => 'nullable|string|max:255',
            'status' => 'required|string|in:Aktif,Nonaktif',
            'bank_account' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
            'parent_id' => 'nullable|exists:sales,id',
        ]);
        
        $latest = \App\Models\Sales::orderBy('id', 'desc')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        $data['member_number'] = 'SL-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        
        \App\Models\Sales::create($data);
        return back()->with('success', 'Sales berhasil ditambahkan.');
    })->name('sales.store');

    Route::put('/sales/{sale}', function (Request $request, \App\Models\Sales $sale) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|string|email|max:255',
            'area' => 'nullable|string|max:255',
            'status' => 'required|string|in:Aktif,Nonaktif',
            'bank_account' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
            'parent_id' => 'nullable|exists:sales,id',
        ]);

        $sale->update($data);
        return back()->with('success', 'Sales berhasil diperbarui.');
    })->name('sales.update');

    Route::delete('/sales/{sale}', function (\App\Models\Sales $sale) {
        $sale->delete();
        return back()->with('success', 'Sales dihapus.');
    })->name('sales.destroy');

    Route::post('/sales/{sale}/account', function (Request $request, \App\Models\Sales $sale) {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        if (!$sale->email) {
            return back()->with('error', 'Sales tidak memiliki email. Silakan isi email terlebih dahulu.');
        }

        if ($sale->user_id) {
            return back()->with('error', 'Sales ini sudah memiliki akun.');
        }

        $user = \App\Models\User::create([
            'name' => $sale->name,
            'email' => $sale->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => 'sales',
        ]);

        $user->assignRole('sales');

        $sale->update(['user_id' => $user->id]);

        return back()->with('success', 'Akun login berhasil dibuat untuk sales.');
    })->name('sales.account.store');

    Route::delete('/sales/{sale}/account', function (\App\Models\Sales $sale) {
        if ($sale->user_id) {
            $user = \App\Models\User::find($sale->user_id);
            if ($user) {
                $user->delete();
            }
            $sale->update(['user_id' => null]);
        }
        return back()->with('success', 'Akun login dihapus.');
    })->name('sales.account.destroy');

    // PENGATURAN (Profile & Payment Methods)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/app-logo', [ProfileController::class, 'updateAppLogo'])->name('profile.app-logo.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::post('/payment-methods', function (Request $request) {
        $request->validate(['name' => 'required|string|max:100']);
        PaymentMethod::create(['name' => $request->name]);
        return back()->with('success', 'Metode pembayaran ditambahkan.');
    })->name('payment-methods.store');

    Route::delete('/payment-methods/{paymentMethod}', function (PaymentMethod $paymentMethod) {
        $paymentMethod->delete();
        return back()->with('success', 'Metode dihapus.');
    })->name('payment-methods.destroy');

    Route::post('/users', function (Request $request) {
        if (!$request->user()->can('manajemen_pengguna')) abort(403);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,staff,sales,finance,manajer',
        ]);

        $user = \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'role' => $data['role']
        ]);

        $user->syncRoles([$data['role']]);

        return back()->with('success', 'Akun berhasil dibuat.');
    })->name('users.store');

    Route::put('/users/{user}', function (Request $request, \App\Models\User $user) {
        if (!$request->user()->can('manajemen_pengguna')) abort(403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:admin,staff,sales,finance,manajer',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$data['role']]);
        return back()->with('success', 'Akun berhasil diperbarui.');
    })->name('users.update');

    Route::delete('/users/{user}', function (Request $request, \App\Models\User $user) {
        if (!$request->user()->can('manajemen_pengguna')) abort(403);
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();
        return back()->with('success', 'Akun dihapus.');
    })->name('users.destroy');


    Route::put('/roles/{role}', function (Request $request, \Spatie\Permission\Models\Role $role) {
        if (!$request->user()->can('manajemen_role') && !$request->user()->can('manajemen_pengguna')) abort(403);
        
        $data = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        return back()->with('success', 'Hak akses role berhasil diperbarui.');
    })->name('roles.update');

    Route::get('/komisi', function (Request $request) {
        $user = auth()->user();
        
        $query = \App\Models\Commission::with(['sales', 'customer.sales', 'transaction'])
            ->orderBy('created_at', 'desc');

        // If user is sales, restrict to their own commissions
        if ($user && $user->hasRole('sales')) {
            $mySalesProfile = \App\Models\Sales::where('user_id', $user->id)->first();
            if ($mySalesProfile) {
                $query->where('sales_id', $mySalesProfile->id);
            } else {
                $query->where('sales_id', -1); // Show empty if no profile
            }
        }

        $commissions = $query->get();
        $paymentMethods = \App\Models\PaymentMethod::orderBy('name')->get();

        return Inertia::render('Komisi', [
            'commissions' => $commissions,
            'paymentMethods' => $paymentMethods,
            'is_sales' => $user && $user->hasRole('sales'),
            'can_pay' => $user && ($user->hasRole('admin') || $user->hasRole('finance')),
        ]);
    })->name('komisi.index');

    Route::post('/komisi/{commission}/pay', function (Request $request, \App\Models\Commission $commission) {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->hasRole('finance')) {
            abort(403);
        }

        if ($commission->status === 'paid') {
            return back()->with('error', 'Komisi sudah dibayarkan sebelumnya.');
        }

        $request->validate([
            'date' => 'required|date',
            'payment_method' => 'nullable|string',
            'proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048'
        ]);

        $path = $request->file('proof')->store('komisi_proof', 'public');

        $commission->update([
            'status' => 'paid',
            'proof_of_payment' => $path,
        ]);

        // Get or create expense category for commission
        $category = \App\Models\ExpenseCategory::firstOrCreate(
            ['name' => 'Pembayaran Komisi'],
            ['name' => 'Pembayaran Komisi']
        );

        // Record expense transaction
        \App\Models\Transaction::create([
            'type' => 'expense',
            'date' => $request->date,
            'description' => 'Pembayaran Komisi ' . ($commission->type === 'booking' ? 'Booking' : 'Bulanan') . ' - ' . ($commission->sales->name ?? 'Sales') . ' (Pelanggan: ' . ($commission->customer->name ?? '-') . ')',
            'amount' => $commission->amount,
            'expense_category_id' => $category->id,
            'payment_method' => $request->payment_method,
            'proof' => $path,
        ]);

        return back()->with('success', 'Komisi berhasil ditandai sebagai sudah dibayar dan tercatat di laporan keuangan.');
    })->name('komisi.pay');

    Route::post('/komisi/{commission}/cancel-pay', function (Request $request, \App\Models\Commission $commission) {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->hasRole('finance')) {
            abort(403);
        }

        if ($commission->status !== 'paid') {
            return back()->with('error', 'Status komisi tidak valid untuk dibatalkan.');
        }

        $proofPath = $commission->proof_of_payment;

        // Delete the transaction linked to this payment
        if ($proofPath) {
            $transaction = \App\Models\Transaction::where('proof', $proofPath)->first();
            if ($transaction) {
                // Remove proof file if exists
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($proofPath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($proofPath);
                }
                $transaction->delete();
            }
        }

        $commission->update([
            'status' => 'pending',
            'proof_of_payment' => null,
        ]);

        return back()->with('success', 'Pembayaran komisi berhasil dibatalkan dan ditarik dari laporan keuangan.');
    })->name('komisi.cancel-pay');


    // Integrasi API Billing
    Route::get('/integrasi-billing', [\App\Http\Controllers\IntegrasiBillingController::class, 'index'])->name('integrasi-billing.index');
    Route::post('/integrasi-billing/update', [\App\Http\Controllers\IntegrasiBillingController::class, 'update'])->name('integrasi-billing.update');
    Route::post('/integrasi-billing/sync', [\App\Http\Controllers\IntegrasiBillingController::class, 'sync'])->name('integrasi-billing.sync');
});
require __DIR__.'/auth.php';
