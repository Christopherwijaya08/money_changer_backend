<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_number',
    'branch_id',
    'type',
    'currency_id',
    'amount',
    'rate_default',
    'rate_actual',
    'total_amount',
    'customer_id',
    'employee_id',
    'user_id',
    'note',
    'requires_review',
])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'rate_default' => 'decimal:2',
            'rate_actual' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'requires_review' => 'boolean',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Margin tracking: gap between the standing rate and what was actually
    // negotiated. Buying cheaper than default, or selling pricier than
    // default, is profit; the other direction is a cost of the negotiation.
    public function margin(): float
    {
        return $this->type === 'buy'
            ? ($this->rate_default - $this->rate_actual) * $this->amount
            : ($this->rate_actual - $this->rate_default) * $this->amount;
    }
}
