<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSuspension extends Model
{
    protected $fillable = ['customer_id', 'suspend_start_date', 'suspend_end_date'];

    protected $casts = [
        'suspend_start_date' => 'date',
        'suspend_end_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
