<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currency = $this->route('currency');

        return [
            'code' => [
                'required',
                'string',
                'regex:/^[A-Za-z]{3}$/',
                Rule::unique('currencies', 'code')->ignore($currency),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
