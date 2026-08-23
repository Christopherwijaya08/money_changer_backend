<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Currency $resource
 */
class ExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestRate = $this->latestExchangeRate;

        return [
            'currency_id' => $this->id,
            'currency_code' => $this->code,
            'rate_buy' => $latestRate?->rate_buy,
            'rate_sell' => $latestRate?->rate_sell,
            'effective_date' => $latestRate?->effective_date,
            'updated_at' => $latestRate?->updated_at,
            'updated_by' => $latestRate?->createdBy?->name,
        ];
    }
}
