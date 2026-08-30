<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payroll_year' => ['required', 'integer', 'between:2000,2200'],
            'payroll_month' => ['required', 'integer', 'between:1,12'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'distinct', 'exists:teachers,id'],
        ];
    }
}
