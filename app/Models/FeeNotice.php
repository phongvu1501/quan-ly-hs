<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeNotice extends Model
{
    protected $fillable = [
        'notice_code',
        'generation_key',
        'academic_year_id',
        'semester_id',
        'class_id',
        'fee_category_id',
        'fee_rate_id',
        'title',
        'amount',
        'period_type',
        'period_key',
        'charge_sequence',
        'assessment_date',
        'scope_type',
        'due_on',
        'description',
        'is_active',
        'status',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'assessment_date' => 'date',
            'due_on' => 'date',
            'charge_sequence' => 'integer',
            'is_active' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    public function isLegacy(): bool
    {
        return ! \Illuminate\Support\Facades\Schema::hasColumn('fee_notices', 'fee_category_id')
            || $this->fee_category_id === null;
    }
    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }
    public function assessments(): HasMany
    {
        return $this->hasMany(FeeAssessment::class);
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
    public function rate(): BelongsTo
    {
        return $this->belongsTo(FeeRate::class, 'fee_rate_id');
    }
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
