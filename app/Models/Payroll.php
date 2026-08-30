<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    public const STATUSES = ['draft', 'calculated', 'approved', 'locked', 'paid', 'cancelled'];

    protected $fillable = [
        'payroll_code',
        'teacher_id',
        'academic_year_id',
        'teacher_salary_profile_id',
        'payroll_year',
        'payroll_month',
        'teacher_code_snapshot',
        'teacher_name_snapshot',
        'specialization_snapshot',
        'academic_year_snapshot',
        'salary_profile_code_snapshot',
        'base_salary',
        'total_allowances',
        'total_teaching_extra',
        'total_bonus',
        'gross_salary',
        'total_insurance',
        'total_tax',
        'total_deductions',
        'net_salary',
        'paid_amount',
        'remaining_amount',
        'status',
        'note',
        'calculated_by',
        'calculated_at',
        'approved_by',
        'approved_at',
        'locked_by',
        'locked_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'payroll_year' => 'integer',
            'payroll_month' => 'integer',
            'base_salary' => 'decimal:2',
            'total_allowances' => 'decimal:2',
            'total_teaching_extra' => 'decimal:2',
            'total_bonus' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'total_insurance' => 'decimal:2',
            'total_tax' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
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

    public function salaryProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherSalaryProfile::class, 'teacher_salary_profile_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }
}
