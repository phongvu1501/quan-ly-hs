<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('feeRate')?->id;

        return [
            'fee_category_id' => ['required', 'integer', 'exists:fee_categories,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'code' => ['required', 'string', 'max:80', Rule::unique('fee_rates', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'period_type' => ['required', Rule::in(['monthly', 'semester', 'annual', 'one_time'])],
            'amount' => ['required', 'decimal:0,2', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
