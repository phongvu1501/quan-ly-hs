<?php

namespace App\Http\Requests\Finance;

use App\Models\FeePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('feePolicy')?->id;

        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'fee_category_id' => ['nullable', 'integer', 'exists:fee_categories,id'],
            'code' => ['required', 'string', 'max:80', Rule::unique('fee_policies', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'reduction_type' => ['required', Rule::in(FeePolicy::REDUCTION_TYPES)],
            'reduction_value' => [
                'required', 'decimal:0,4', 'min:0',
                Rule::when($this->input('reduction_type') === 'percentage', ['max:100']),
                Rule::when($this->input('reduction_type') === 'full_exemption', ['in:100,100.0,100.00,100.000,100.0000']),
            ],
            'application_mode' => ['required', Rule::in(FeePolicy::APPLICATION_MODES)],
            'priority' => ['required', 'integer', 'between:-100000,100000'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
