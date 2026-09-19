<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function incomeCategory()
    {
        return $this->belongsTo(IncomeCategory::class);
    }

    public function companyExpenseType()
    {
        return $this->belongsTo(CompanyExpenseType::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function parent()
    {
        return $this->belongsTo(Transaction::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Transaction::class, 'parent_id');
    }
}
