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

        // Optimize: preload all related transactions to prevent N+1 queries
        $descriptions = $customers->pluck('name')->map(function($name) {
            return 'Pembayaran dari ' . $name;
        })->toArray();
        
        $allTransactions = \App\Models\Transaction::where('type', 'income')
            ->whereIn('description', $descriptions)
            ->select('description', 'date', 'amount')
            ->get()
            ->groupBy('description');

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
                    // In registration month: show status 'prorata' with prorata_amount (info only, not billable yet)
                    // Next month: diff=1 → prorata amount becomes billable
                    // Month after: diff=2 → prorata + 1x base_amount, etc.
                    
                    if ($registerMonth->eq($currentMonth)) {
                        // Registration month: show prorata status & amount, but not yet billable
                        if ($c->status !== 'prorata' || $c->amount != $c->prorata_amount || $c->is_partial_payment != 0) {
                            $c->update(['status' => 'prorata', 'amount' => $c->prorata_amount, 'is_partial_payment' => 0]);
                        }
                        continue; // Skip rest of billing logic
                    }
                    
                    $diff = $registerMonth->diffInMonths($currentMonth, false);
                    $startOfUnpaidPeriod = $registerMonth->copy()->addMonth();
                } else {
                    // No prorata: owe for every month since registration inclusive
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
                
                if (isset($allTransactions[$descKey])) {
                    foreach ($allTransactions[$descKey] as $t) {
                        if (\Carbon\Carbon::parse($t->date)->gte($startOfUnpaidPeriod)) {
                            $totalPaid += $t->amount;
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
