<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeAssessment extends Model
{
    public const STATUSES = ['pending', 'partially_paid', 'paid', 'cancelled'];

    protected $fillable = [
        'assessment_code',
        'fee_notice_id',
        'student_id',
        'academic_year_id',
        'semester_id',
        'class_id',
        'fee_category_id',
        'period_type',
        'period_key',
        'charge_sequence',
        'assessment_date',
        'due_date',
        'student_code_snapshot',
        'student_name_snapshot',
        'class_code_snapshot',
        'class_name_snapshot',
        'fee_category_code_snapshot',
        'fee_category_name_snapshot',
        'fee_notice_title_snapshot',
        'original_amount',
        'discount_amount',
        'final_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'note',
        'generated_by',
        'locked_by',
        'locked_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
            'due_date' => 'date',
            'charge_sequence' => 'integer',
            'original_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'locked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(FeeNotice::class, 'fee_notice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(FeeAssessmentDiscount::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
