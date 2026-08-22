<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
        ];
    }
}
