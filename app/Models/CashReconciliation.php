<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id',
    'currency_id',
    'date',
    'opening_balance',
    'cash_in',
    'cash_out',
    'system_balance',
    'physical_balance',
    'difference',
    'created_by',
])]
class CashReconciliation extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'opening_balance' => 'decimal:2',
            'cash_in' => 'decimal:2',
            'cash_out' => 'decimal:2',
            'system_balance' => 'decimal:2',
            'physical_balance' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
