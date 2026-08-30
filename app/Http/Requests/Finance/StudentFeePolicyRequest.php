<?php

namespace App\Http\Requests\Finance;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StudentFeePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'fee_policy_id' => ['required', 'integer', 'exists:fee_policies,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'evidence_reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'active') {
                return;
            }
            $student = Student::find($this->integer('student_id'));
            if ($student && ! $student->isStudying()) {
                $validator->errors()->add(
                    'student_id',
                    'Chỉ học sinh đang học mới được gán diện miễn giảm đang áp dụng.'
                );
            }
        });
    }
}
