<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('taxConfig')?->id;

        return [
            'code' => ['required', 'string', 'max:80', Rule::unique('tax_configs', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'tax_year' => ['required', 'integer', 'between:2000,2200'],
            'personal_deduction' => ['required', 'decimal:0,2', 'min:0'],
            'dependent_deduction' => ['required', 'decimal:0,2', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'brackets' => ['required', 'array', 'min:1'],
            'brackets.*.sequence' => ['required', 'integer', 'distinct', 'min:1'],
            'brackets.*.lower_bound' => ['required', 'decimal:0,2', 'min:0'],
            'brackets.*.upper_bound' => ['nullable', 'decimal:0,2', 'gt:brackets.*.lower_bound'],
            'brackets.*.rate_percentage' => ['required', 'decimal:0,4', 'between:0,100'],
            'brackets.*.quick_deduction' => ['required', 'decimal:0,2', 'min:0'],
        ];
    }
}
