<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['opening_balance' => 'decimal:2', 'is_active' => 'boolean'];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getBalanceAttribute(): float
    {
        $income = (float) ($this->income_sum ?? $this->transactions()->where('type', 'income')->sum('amount'));
        $expense = (float) ($this->expense_sum ?? $this->transactions()->where('type', 'expense')->sum('amount'));

        return round((float) $this->opening_balance + $income - $expense, 2);
    }
}
