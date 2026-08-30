<?php

namespace App\Http\Requests\School;

use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Teacher $teacher */
        $teacher = $this->route('teacher');
        $emailPresence = $teacher->user_id ? 'required' : 'nullable';

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                $emailPresence, 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($teacher->user_id),
                Rule::unique('teachers', 'email')->ignore($teacher->id),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]+$/'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Teacher::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['integer', 'distinct', Rule::exists('subjects', 'id')->where('is_active', true)],
            'homeroom_class_ids' => ['nullable', 'array', 'max:1'],
            'homeroom_class_ids.*' => ['integer', 'distinct', Rule::exists('classes', 'id')->where('status', 'active')],
            'is_homeroom_teacher' => ['nullable', 'boolean'],
        ];
    }
}
