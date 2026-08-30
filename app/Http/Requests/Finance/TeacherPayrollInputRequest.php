<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherPayrollInputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'salary_component_id' => ['required', 'integer', 'exists:salary_components,id'],
            'input_year' => ['required', 'integer', 'between:2000,2200'],
            'input_month' => ['required', 'integer', 'between:1,12'],
            'input_type' => ['required', Rule::in([
                'working_days', 'unpaid_leave', 'overtime', 'bonus',
                'manual_adjustment', 'other_input',
            ])],
            'quantity' => ['nullable', 'decimal:0,2', 'min:0'],
            'input_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'approved'])],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
