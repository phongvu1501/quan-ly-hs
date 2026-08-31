<?php

namespace App\Http\Requests\School;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:students,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]+$/'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'admission_year' => ['nullable', 'regex:/^(\d{4})-(\d{4})$/', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value && (int) substr($value, 5, 4) !== (int) substr($value, 0, 4) + 1) {
                    $fail('Năm tuyển sinh phải có dạng YYYY-YYYY với hai năm liên tiếp.');
                }
            }],
            'status' => [
                'required',
                Rule::in(Student::STATUSES),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === Student::STATUS_GRADUATED) {
                        $fail('Không thể tạo mới học sinh ở trạng thái "Đã tốt nghiệp".');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'guardians' => ['nullable', 'array'],
            'guardians.*.full_name' => ['nullable', 'string', 'max:255'],
            'guardians.*.date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'guardians.*.phone' => ['nullable', 'string', 'max:30'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],
            'guardians.*.occupation' => ['nullable', 'string', 'max:255'],
            'guardians.*.religion' => ['nullable', 'string', 'max:100'],
            'guardians.*.workplace' => ['nullable', 'string', 'max:255'],
            'guardians.*.address' => ['nullable', 'string', 'max:255'],
            'guardians.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
