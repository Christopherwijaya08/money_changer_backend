<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'old_buy' => $this->old_buy,
            'old_sell' => $this->old_sell,
            'new_buy' => $this->new_buy,
            'new_sell' => $this->new_sell,
            'changed_by' => $this->changedBy?->name,
            'changed_at' => $this->changed_at,
        ];
    }
}
