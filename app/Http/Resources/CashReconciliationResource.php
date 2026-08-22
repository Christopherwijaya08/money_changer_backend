<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashReconciliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->code,
            'date' => $this->date,
            'opening_balance' => $this->opening_balance,
            'cash_in' => $this->cash_in,
            'cash_out' => $this->cash_out,
            'system_balance' => $this->system_balance,
            'physical_balance' => $this->physical_balance,
            'difference' => $this->difference,
            'created_by' => $this->createdBy?->name,
        ];
    }
}
