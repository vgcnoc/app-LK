<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpgradeHistory extends Model
{
    protected $fillable = ['customer_id', 'old_paket', 'new_paket'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
