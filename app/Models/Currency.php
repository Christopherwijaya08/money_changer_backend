<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['code'])]
class Currency extends Model
{
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    public function latestExchangeRate(): HasOne
    {
        return $this->hasOne(ExchangeRate::class)->latestOfMany(['effective_date', 'id']);
    }
}
