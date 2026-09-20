<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiBillingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_name',
        'base_url',
        'api_key',
        'json_mapping',
        'last_sync_at',
    ];

    protected $casts = [
        'json_mapping' => 'array',
        'last_sync_at' => 'datetime',
    ];
}
