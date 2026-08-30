<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class TeachingExtraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'teaching_year' => ['required', 'integer', 'between:2000,2200'],
            'teaching_month' => ['required', 'integer', 'between:1,12'],
            'number_of_periods' => ['required', 'decimal:0,2', 'gt:0'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
