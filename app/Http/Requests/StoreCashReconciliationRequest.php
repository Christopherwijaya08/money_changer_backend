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
        ];
    }
}
