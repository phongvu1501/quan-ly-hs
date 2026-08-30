<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InsuranceConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('insuranceConfig')?->id;

        return [
            'code' => ['required', 'string', 'max:80', Rule::unique('insurance_configs', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'employee_rate_percentage' => ['required', 'decimal:0,4', 'between:0,100'],
            'employer_rate_percentage' => ['required', 'decimal:0,4', 'between:0,100'],
            'salary_base_type' => ['required', Rule::in(['base_salary', 'gross', 'insurance_earnings', 'fixed'])],
            'fixed_salary_base' => ['nullable', 'decimal:0,2', 'min:0'],
            'maximum_salary_base' => ['nullable', 'decimal:0,2', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
