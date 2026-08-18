<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['currency_id', 'rate_buy', 'rate_sell', 'effective_date', 'created_by'])]
class ExchangeRate extends Model
{
    protected function casts(): array
    {
        return [
            'rate_buy' => 'decimal:2',
            'rate_sell' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ExchangeRateHistory::class);
    }
}
