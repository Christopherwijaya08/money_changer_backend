<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'review_threshold' => $this->review_threshold,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updatedBy?->name,
        ];
    }
}
