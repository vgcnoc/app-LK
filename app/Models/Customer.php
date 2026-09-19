<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    public static function syncBilling()
    {
        $customers = self::whereIn('status_pelanggan', ['Aktif', 'Gratis', ''])->orWhereNull('status_pelanggan')->get();
        $currentMonth = \Carbon\Carbon::now()->startOfMonth();

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
                // If never paid, they owe for every month since registration inclusive
                $registerMonth = \Carbon\Carbon::parse($c->register_date ?? $c->created_at)->startOfMonth();
                $diff = $registerMonth->diffInMonths($currentMonth, false) + 1;
                $startOfUnpaidPeriod = $registerMonth;
            }

            if ($diff <= 0) {
                // Paid this month or future (Lunas)
                if ($c->status !== 'paid' || $c->is_partial_payment != 0) {
                    $c->update(['status' => 'paid', 'amount' => 0, 'is_partial_payment' => 0]);
                }
            } else {
                $totalPaid = \App\Models\Transaction::where('type', 'income')
                    ->where('description', 'Pembayaran dari ' . $c->name)
                    ->where('date', '>=', $startOfUnpaidPeriod)
                    ->sum('amount');
                
                $billableMonths = max(0, $diff - 1);
                
                if ($c->prorata_amount !== null) {
                    $expectedAmount = $c->prorata_amount + ($c->base_amount * $billableMonths) - $totalPaid;
                } else {
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
