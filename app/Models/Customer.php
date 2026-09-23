<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    public static function calculateProrata($baseAmount, $registerDate)
    {
        if (!$registerDate) return null;
        
        try {
            $regDate = \Carbon\Carbon::parse($registerDate);
            $now = \Carbon\Carbon::now();
            
            // Only calculate prorata if register date is in the CURRENT month and year
            if ($regDate->month !== $now->month || $regDate->year !== $now->year) {
                return null;
            }
            
            $daysInMonth = $regDate->daysInMonth;
            $remainingDays = $daysInMonth - $regDate->day + 1;
            
            // If they registered on the 1st, they pay full amount, so no prorata needed
            if ($remainingDays >= $daysInMonth) return null;
            
            return round(($baseAmount / $daysInMonth) * $remainingDays);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function syncBilling()
    {
        $customers = self::whereIn('status_pelanggan', ['Aktif', 'Gratis', ''])->orWhereNull('status_pelanggan')->get();
        if ($customers->isEmpty()) return;

        $currentMonth = \Carbon\Carbon::now()->startOfMonth();

        // Get all transactions linked to customers
        $customerIds = $customers->pluck('id')->toArray();
        $allTransactions = \App\Models\Transaction::where('type', 'income')
            ->whereIn('customer_id', $customerIds)
            ->select('customer_id', 'description', 'date', 'amount')
            ->get()
            ->groupBy('customer_id');

        // Fallback for old transactions without customer_id
        $oldTransactions = \App\Models\Transaction::where('type', 'income')
            ->whereNull('customer_id')
            ->select('description', 'date', 'amount')
            ->get();

        foreach ($customers as $c) {
            // Jika status pelanggan = Gratis
            if ($c->status_pelanggan === 'Gratis') {
                if ($c->status !== 'paid' || $c->amount != 0 || $c->is_partial_payment != 0) {
                    $c->update(['status' => 'paid', 'amount' => 0, 'is_partial_payment' => 0]);
                }
                continue;
            }

            // Calculate months owed
            if ($c->last_paid_date) {
                $refMonth = \Carbon\Carbon::parse($c->last_paid_date)->startOfMonth();
                $diff = $refMonth->diffInMonths($currentMonth, false);
                $startOfUnpaidPeriod = $refMonth->copy()->addMonth();
            } else {
                // If never paid, calculate months owed since registration
                $registerMonth = \Carbon\Carbon::parse($c->register_date ?? $c->created_at)->startOfMonth();
                
                if ($c->prorata_amount !== null) {
                    // Prorata: billing starts NEXT month after registration
                    if ($registerMonth->eq($currentMonth)) {
                        if ($c->status !== 'prorata' || $c->amount != $c->prorata_amount || $c->is_partial_payment != 0) {
                            $c->update(['status' => 'prorata', 'amount' => $c->prorata_amount, 'is_partial_payment' => 0]);
                        }
                        continue; 
                    }
                    
                    $diff = $registerMonth->diffInMonths($currentMonth, false);
                    $startOfUnpaidPeriod = $registerMonth->copy()->addMonth();
                } else {
                    $diff = $registerMonth->diffInMonths($currentMonth, false) + 1;
                    $startOfUnpaidPeriod = $registerMonth;
                }
            }

            if ($diff <= 0) {
                // Paid this month or future (Lunas)
                if ($c->status !== 'paid' || $c->is_partial_payment != 0) {
                    $c->update(['status' => 'paid', 'amount' => 0, 'is_partial_payment' => 0]);
                }
            } else {
                $descKey = 'Pembayaran dari ' . $c->name;
                $totalPaid = 0;
                
                // Calculate from new transactions
                if (isset($allTransactions[$c->id])) {
                    foreach ($allTransactions[$c->id] as $t) {
                        if (\Carbon\Carbon::parse($t->date)->gte($startOfUnpaidPeriod)) {
                            $totalPaid += $t->amount;
                            // Extract diskon
                            if (preg_match('/\(Diskon:\s*Rp\s*([\d\.]+)\)/', $t->description, $matches)) {
                                $totalPaid += floatval(str_replace('.', '', $matches[1]));
                            }
                        }
                    }
                }

                // Calculate from old transactions
                foreach ($oldTransactions as $t) {
                    if (str_starts_with($t->description, $descKey) && \Carbon\Carbon::parse($t->date)->gte($startOfUnpaidPeriod)) {
                        $totalPaid += $t->amount;
                        if (preg_match('/\(Diskon:\s*Rp\s*([\d\.]+)\)/', $t->description, $matches)) {
                            $totalPaid += floatval(str_replace('.', '', $matches[1]));
                        }
                    }
                }
                
                if ($c->prorata_amount !== null) {
                    $billableMonths = max(0, $diff - 1);
                    $expectedAmount = $c->prorata_amount + ($c->base_amount * $billableMonths) - $totalPaid;
                } else {
                    $billableMonths = max(0, $diff);
                    $expectedAmount = ($c->base_amount * $billableMonths) - $totalPaid;
                }
                if ($expectedAmount < 0) $expectedAmount = 0;

                if ($expectedAmount <= 0) {
                    if ($c->status !== 'paid' || $c->is_partial_payment != 0) {
                        $c->update(['status' => 'paid', 'amount' => 0, 'is_partial_payment' => 0]);
                    }
                } else {
                    // Determine if pending, nunggak, or prorata
                    // If diff == 1 and prorata is active, status is prorata
                    $isPartial = $totalPaid > 0 ? 1 : 0;
                    
                    if ($diff == 1 && $c->prorata_amount !== null) {
                        if ($c->status !== 'prorata' || $c->amount != $expectedAmount || $c->is_partial_payment != $isPartial) {
                            $c->update(['status' => 'prorata', 'amount' => $expectedAmount, 'is_partial_payment' => $isPartial]);
                        }
                    } else {
                        // For diff >= 2, check how much they owe relative to base_amount
                        // If they owe <= base_amount, they just owe for the current month -> pending (Belum Bayar)
                        // If they owe > base_amount, they owe for current + previous -> nunggak
                        if ($expectedAmount <= $c->base_amount) {
                            if ($c->status !== 'pending' || $c->amount != $expectedAmount || $c->is_partial_payment != $isPartial) {
                                $c->update(['status' => 'pending', 'amount' => $expectedAmount, 'is_partial_payment' => $isPartial]);
                            }
                        } else {
                            if ($c->status !== 'nunggak' || $c->amount != $expectedAmount || $c->is_partial_payment != $isPartial) {
                                $c->update(['status' => 'nunggak', 'amount' => $expectedAmount, 'is_partial_payment' => $isPartial]);
                            }
                        }
                    }
                }
            }
        }
    }

    public function payments()
    {
        return $this->hasMany(Transaction::class);
    }

    public function sales()
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function suspensions()
    {
        return $this->hasMany(CustomerSuspension::class)->orderBy('suspend_start_date', 'desc');
    }
}
