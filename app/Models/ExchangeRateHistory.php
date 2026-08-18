<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exchange_rate_id', 'old_buy', 'old_sell', 'new_buy', 'new_sell', 'changed_by'])]
class ExchangeRateHistory extends Model
{
    protected $table = 'exchange_rate_history';

    const UPDATED_AT = null;
    const CREATED_AT = 'changed_at';

    protected function casts(): array
    {
        return [
            'old_buy' => 'decimal:2',
            'old_sell' => 'decimal:2',
            'new_buy' => 'decimal:2',
            'new_sell' => 'decimal:2',
            'changed_at' => 'datetime',
        ];
    }

    public function exchangeRate(): BelongsTo
    {
        return $this->belongsTo(ExchangeRate::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
