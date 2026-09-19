<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'area',
        'alamat',
        'phone',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
