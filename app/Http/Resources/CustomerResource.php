<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'identity_number' => $this->identity_number,
            'phone' => $this->phone,
            'address' => $this->address,
            'has_ktp_photo' => ! is_null($this->ktp_photo_path),
        ];
    }
}
