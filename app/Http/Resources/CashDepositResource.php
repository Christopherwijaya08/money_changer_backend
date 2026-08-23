<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashDepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->code,
            'amount' => $this->amount,
            'note' => $this->note,
            'created_by' => $this->createdBy?->name,
            'created_at' => $this->created_at,
        ];
    }
}
