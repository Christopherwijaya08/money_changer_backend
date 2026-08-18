<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rate_buy' => ['required', 'numeric', 'gt:0'],
            'rate_sell' => ['required', 'numeric', 'gt:0'],
            // ponytail: client-supplied until Sanctum auth (Fase 5) lands, then read from $request->user() instead
            'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
