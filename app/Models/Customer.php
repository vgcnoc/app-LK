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
                if ($c->status !== 'paid' || $c->amount != 0) {
                    $c->update(['status' => 'paid', 'amount' => 0]);
                }
                continue;
            }

            // Normal Active Customer
            $referenceDate = $c->last_paid_date ? \Carbon\Carbon::parse($c->last_paid_date) : \Carbon\Carbon::parse($c->created_at);
            $refMonth = $referenceDate->copy()->startOfMonth();

            $diff = $refMonth->diffInMonths($currentMonth, false);

            if ($diff <= 0) {
                // Paid this month or future (Lunas)
                if ($c->status !== 'paid') {
                    $c->update(['status' => 'paid', 'amount' => 0]);
                }
            } else if ($diff == 1) {
                // Owes 1 month (Belum Bayar)
                if ($c->status !== 'pending' || $c->amount != $c->base_amount) {
                    $c->update(['status' => 'pending', 'amount' => $c->base_amount]);
                }
            } else {
                // Owes multiple months (Nunggak)
                $newAmount = $c->base_amount * $diff;
                if ($c->status !== 'nunggak' || $c->amount != $newAmount) {
                    $c->update(['status' => 'nunggak', 'amount' => $newAmount]);
                }
            }
        }
    }
}
