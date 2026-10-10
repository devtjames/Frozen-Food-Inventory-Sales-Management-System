<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'normalized_name'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
