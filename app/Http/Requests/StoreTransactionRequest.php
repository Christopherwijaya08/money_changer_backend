<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'type' => ['required', 'in:buy,sell'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'rate_default' => ['required', 'numeric', 'gt:0'],
            'rate_actual' => ['required', 'numeric', 'gt:0'],
            'customer_id' => ['required', 'exists:customers,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            // ponytail: client-supplied until Sanctum auth (Fase 5) lands, then read from $request->user() instead
            'user_id' => ['required', 'exists:users,id'],
            'note' => ['nullable', 'string'],
        ];
    }
}
