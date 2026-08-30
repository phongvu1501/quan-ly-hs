<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherPayrollInput extends Model
{
    protected $fillable = [
        'input_code',
        'teacher_id',
        'academic_year_id',
        'salary_component_id',
        'included_payroll_id',
        'input_year',
        'input_month',
        'input_type',
        'quantity',
        'input_amount',
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
            'quantity' => 'decimal:2',
            'input_amount' => 'decimal:2',
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

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }

    public function includedPayroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'included_payroll_id');
    }
}
