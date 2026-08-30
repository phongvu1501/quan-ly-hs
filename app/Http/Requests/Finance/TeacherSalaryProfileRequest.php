<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherSalaryProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('salaryProfile')?->id;

        return [
            'profile_code' => ['required', 'string', 'max:80', Rule::unique('teacher_salary_profiles', 'profile_code')->ignore($id)],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'dependent_count' => ['required', 'integer', 'min:0'],
            'tax_enabled' => ['nullable', 'boolean'],
            'insurance_enabled' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'note' => ['nullable', 'string', 'max:5000'],
            'configs' => ['required', 'array', 'min:1'],
            'configs.*.salary_component_id' => ['required', 'integer', 'exists:salary_components,id'],
            'configs.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'configs.*.fixed_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'configs.*.percentage_value' => ['nullable', 'decimal:0,4', 'between:0,100'],
            'configs.*.rate_per_unit' => ['nullable', 'decimal:0,2', 'min:0'],
            'configs.*.default_quantity' => ['nullable', 'decimal:0,2', 'min:0'],
            'configs.*.calculation_base' => ['nullable', Rule::in(['base_salary', 'gross'])],
            'configs.*.is_active' => ['nullable', 'boolean'],
            'configs.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
