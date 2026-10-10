<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseRevision extends Model
{
    protected $fillable = ['idempotency_key', 'expense_id', 'previous_values', 'updated_values', 'reason', 'changed_at', 'changed_by'];

    protected function casts(): array
    {
        return [
            'previous_values' => 'array',
            'updated_values' => 'array',
            'changed_at' => 'datetime',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
