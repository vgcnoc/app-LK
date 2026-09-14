<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\PaymentMethod;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    
    // DASHBOARD
    Route::get('/dashboard', function () {
        \App\Models\Customer::syncBilling();

        // Global summaries
        $totalIncome = Transaction::where('type', 'income')->sum('amount');
        $totalExpense = Transaction::where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // Area summaries
        $areaSummaries = Transaction::select('area', 
            DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income"),
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
            
            $income = Transaction::where('date', $date)->where('type', 'income')->sum('amount');
            $expense = Transaction::where('date', $date)->where('type', 'expense')->sum('amount');
            
            $chartIncome[] = $income;
            $chartExpense[] = $expense;
        }

        // Recent transactions
        $transactions = Transaction::orderBy('date', 'desc')->orderBy('id', 'desc')->limit(10)->get();

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

        return Inertia::render('Dashboard', [
            'summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'balance' => $balance
            ],
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
            ]
        ]);
    })->name('dashboard');

    // TRANSACTIONS
    Route::post('/transactions', function (Request $request) {
        $data = $request->validate([
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
            'area' => 'nullable|string',
            'expense_category_id' => 'nullable|exists:expense_categories,id'
        ]);
        
        if ($data['type'] === 'income') {
            $data['expense_category_id'] = null;
        }

        Transaction::create($data);
        return back()->with('success', 'Transaksi berhasil ditambahkan.');
    })->name('transactions.store');

    Route::delete('/transactions/{transaction}', function (Transaction $transaction) {
        $transaction->delete();
        return back()->with('success', 'Transaksi dihapus.');
    })->name('transactions.destroy');

    // MASTER DATA
    Route::get('/master-data', function () {
        return Inertia::render('MasterData', [
            'expenseCategories' => App\Models\ExpenseCategory::orderBy('name')->get(),
            'paymentMethods' => App\Models\PaymentMethod::orderBy('name')->get()
        ]);
    })->name('master-data.index');

    Route::post('/master-data/expense-categories', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        App\Models\ExpenseCategory::create($data);
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    })->name('expense-categories.store');

    Route::delete('/master-data/expense-categories/{category}', function (App\Models\ExpenseCategory $category) {
        $category->delete();
        return back()->with('success', 'Kategori dihapus.');
    })->name('expense-categories.destroy');

    // BILLING DATA (Pindah dari /pelanggan sebelumnya)
    Route::get('/billing', function () {
        \App\Models\Customer::syncBilling();
        $customers = Customer::whereNull('status_pelanggan')
            ->orWhereIn('status_pelanggan', ['Aktif', ''])
            ->orderBy('created_at', 'desc')
            ->get();
        $paymentMethods = PaymentMethod::orderBy('name')->get();
        return Inertia::render('Billing', [
            'customers' => $customers,
            'paymentMethods' => $paymentMethods
        ]);
    })->name('billing.index');

    Route::post('/billing/import', function (Request $request) {
        $data = $request->validate([
            'customersData' => 'required|array'
        ]);
        
        foreach ($data['customersData'] as $row) {
            Customer::create([
                'name' => $row['name'],
                'amount' => $row['amount'],
                'base_amount' => $row['amount'],
                'area' => $row['area'] ?? null,
                'alamat' => $row['alamat'] ?? null,
                'paket' => $row['paket'] ?? null,
                'register_date' => !empty($row['register_date']) ? date('Y-m-d', strtotime($row['register_date'])) : null,
                'status_pelanggan' => $row['status_pelanggan'] ?? 'Aktif',
                'status' => 'pending',
                'last_paid_date' => !empty($row['last_paid_date']) ? date('Y-m-d', strtotime($row['last_paid_date'])) : null,
            ]);
        }
        return back()->with('success', 'Data pelanggan berhasil diimport.');
    })->name('billing.import');

    Route::post('/billing/{customer}/lunas', function (Request $request, Customer $customer) {
        if ($customer->status === 'paid') {
            return back()->with('error', 'Pelanggan sudah lunas.');
        }

        $paymentMethod = $request->input('payment_method', 'Tunai');

        DB::transaction(function () use ($customer, $paymentMethod) {
            $paidAmount = $customer->amount > 0 ? $customer->amount : $customer->base_amount;

            $customer->update([
                'status' => 'paid',
                'amount' => 0,
                'last_paid_date' => now()->toDateString()
            ]);
            
            Transaction::create([
                'type' => 'income',
                'date' => now()->toDateString(),
                'description' => 'Pembayaran dari ' . $customer->name,
                'amount' => $paidAmount,
                'area' => $customer->area,
                'payment_method' => $paymentMethod
            ]);
        });

        return back()->with('success', 'Pelanggan berhasil ditandai lunas.');
    })->name('billing.lunas');
    
    // PELANGGAN INAKTIF (Berhenti / Stop / Gratis)
    Route::get('/pelanggan-inaktif', function () {
        \App\Models\Customer::syncBilling();
        $customers = Customer::whereIn('status_pelanggan', ['Berhenti sementara', 'Stop Permanen', 'Gratis'])
            ->orderBy('created_at', 'desc')
            ->get();
        return Inertia::render('PelangganInaktif', [
            'customers' => $customers
        ]);
    })->name('pelanggan.inaktif');
    
    // MASTER DATA PELANGGAN
    Route::get('/pelanggan', function () {
        // Halaman ini khusus untuk Master Data Pelanggan (CRUD basic)
        $customers = Customer::orderBy('created_at', 'desc')->get();
        return Inertia::render('Pelanggan', [
            'customers' => $customers
        ]);
    })->name('pelanggan.index');

    Route::post('/pelanggan', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'base_amount' => 'required|numeric|min:0',
            'area' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'paket' => 'nullable|string|max:255',
            'register_date' => 'nullable|date',
            'status_pelanggan' => 'nullable|string|max:255',
        ]);
        
        $data['amount'] = 0; // Default amount
        $data['status'] = 'pending';
        Customer::create($data);
        return back()->with('success', 'Data pelanggan baru berhasil ditambahkan.');
    })->name('pelanggan.store');

    Route::delete('/pelanggan/{customer}', function (Customer $customer) {
        $customer->delete();
        return back()->with('success', 'Pelanggan dihapus.');
    })->name('pelanggan.destroy');

    Route::put('/pelanggan/{customer}', function (Request $request, Customer $customer) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'paket' => 'nullable|string|max:255',
            'register_date' => 'nullable|date',
            'status_pelanggan' => 'nullable|string|max:255',
            'suspend_start_date' => 'nullable|date',
            'suspend_end_date' => 'nullable|date',
            'stop_date' => 'nullable|date',
            'last_paid_date' => 'nullable|date',
            'base_amount' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0'
        ]);

        $customer->update($data);
        return back()->with('success', 'Data pelanggan berhasil diperbarui.');
    })->name('pelanggan.update');

    // LAPORAN
    Route::get('/laporan', function (Request $request) {
        \App\Models\Customer::syncBilling();
        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-t'));
        $areaFilter = $request->query('area', '');
        $kategori = $request->query('kategori', '');

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
        $query = Transaction::whereBetween('date', [$startDate, $endDate]);
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

        $transactions = $query->with('expenseCategory')->orderBy('date', 'asc')->orderBy('id', 'asc')->get();
        
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        
        $areas = Transaction::whereNotNull('area')->where('area', '!=', '')->distinct()->pluck('area');
        $expenseCategories = App\Models\ExpenseCategory::orderBy('name')->get();

        return Inertia::render('Laporan', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'area' => $areaFilter,
                'kategori' => $kategori
            ],
            'summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'balance' => $totalIncome - $totalExpense
            ],
            'transactions' => $transactions,
            'areas' => $areas,
            'expenseCategories' => $expenseCategories,
            'unpaid' => [
                'count' => $unpaidCount,
                'total' => (float)$unpaidTotal,
            ],
            'unpaid_list' => $unpaidList
        ]);
    })->name('laporan');

    // PENGATURAN (Profile & Payment Methods)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
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
        if ($request->user()->role !== 'admin') abort(403);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,staff'
        ]);

        \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'role' => $data['role']
        ]);

        return back()->with('success', 'Akun berhasil dibuat.');
    })->name('users.store');

    Route::put('/users/{user}', function (Request $request, \App\Models\User $user) {
        if ($request->user()->role !== 'admin') abort(403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:admin,staff'
        ]);

        if (!empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return back()->with('success', 'Akun berhasil diperbarui.');
    })->name('users.update');

    Route::delete('/users/{user}', function (Request $request, \App\Models\User $user) {
        if ($request->user()->role !== 'admin') abort(403);
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();
        return back()->with('success', 'Akun dihapus.');
    })->name('users.destroy');

});

require __DIR__.'/auth.php';
