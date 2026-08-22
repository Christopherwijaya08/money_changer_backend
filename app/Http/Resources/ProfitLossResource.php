<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfitLossResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'created_at' => $this->created_at,
            'type' => $this->type,
            'currency_code' => $this->currency?->code,
            'amount' => $this->amount,
            'rate_default' => $this->rate_default,
            'rate_actual' => $this->rate_actual,
            'margin' => $this->margin(),
        ];
    }
}
