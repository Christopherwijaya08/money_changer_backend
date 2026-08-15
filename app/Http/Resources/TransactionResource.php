<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'currency_code' => $this->currency?->code,
            'amount' => $this->amount,
            'rate_default' => $this->rate_default,
            'rate_actual' => $this->rate_actual,
            'total_amount' => $this->total_amount,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee?->name,
            'note' => $this->note,
            'requires_review' => $this->requires_review,
            'created_at' => $this->created_at,
        ];
    }
}
