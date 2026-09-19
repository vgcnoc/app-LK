<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Affiliate extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(Affiliate::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Affiliate::class, 'parent_id');
    }
}
