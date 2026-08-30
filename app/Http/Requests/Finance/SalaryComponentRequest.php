<?php

namespace App\Http\Requests\Finance;

use App\Models\SalaryComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('salaryComponent')?->id;

        return [
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Z0-9_:-]+$/', Rule::unique('salary_components', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(SalaryComponent::TYPES)],
            'category' => ['required', 'string', 'max:40'],
            'calculation_type' => ['required', Rule::in(SalaryComponent::CALCULATION_TYPES)],
            'is_taxable' => ['nullable', 'boolean'],
            'is_insurance_base' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
