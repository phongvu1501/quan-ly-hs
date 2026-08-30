<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingExtraRecord extends Model
{
    protected $fillable = [
        'record_code',
        'record_key',
        'teacher_id',
        'academic_year_id',
        'semester_id',
        'subject_id',
        'class_id',
        'teaching_assignment_id',
        'teacher_salary_config_id',
        'included_payroll_id',
        'teaching_year',
        'teaching_month',
        'number_of_periods',
        'rate_per_period',
        'total_amount',
        'subject_code_snapshot',
        'subject_name_snapshot',
        'class_code_snapshot',
        'class_name_snapshot',
        'status',
        'note',
        'created_by',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'number_of_periods' => 'decimal:2',
            'rate_per_period' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function salaryConfig(): BelongsTo
    {
        return $this->belongsTo(TeacherSalaryConfig::class, 'teacher_salary_config_id');
    }

    public function includedPayroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'included_payroll_id');
    }
}
