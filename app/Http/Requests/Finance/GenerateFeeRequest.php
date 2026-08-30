<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'fee_category_id' => ['required', 'integer', 'exists:fee_categories,id'],
            'fee_rate_id' => ['required', 'integer', 'exists:fee_rates,id'],
            'title' => ['required', 'string', 'max:255'],
            'period_type' => ['required', Rule::in(['monthly', 'semester', 'annual', 'one_time'])],
            'period_key' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9:_-]+$/'],
            'charge_sequence' => ['required', 'integer', 'between:1,65535'],
            'assessment_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:assessment_date'],
            'scope_type' => ['required', Rule::in(['all', 'class', 'selected'])],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'distinct', 'exists:students,id'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
