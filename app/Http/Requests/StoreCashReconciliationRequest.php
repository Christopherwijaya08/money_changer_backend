<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'date' => ['required', 'date'],
            'physical_balance' => ['required', 'numeric'],
            // ponytail: client-supplied until Sanctum auth (Fase 5) lands, then read from $request->user() instead
            'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
