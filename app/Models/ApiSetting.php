<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_name',
        'base_url',
        'api_key',
        'api_secret',
        'json_mapping',
        'last_sync_at',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'json_mapping' => 'array',
    ];
}
