<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeePolicy extends Model
{
    public const REDUCTION_TYPES = ['percentage', 'fixed_amount', 'full_exemption'];

    public const APPLICATION_MODES = ['exclusive', 'cumulative'];

    protected $fillable = [
        'academic_year_id', 'fee_category_id', 'code', 'name', 'reduction_type',
        'reduction_value', 'application_mode', 'priority', 'effective_from',
        'effective_to', 'status', 'description',
    ];

    protected function casts(): array
    {
        return [
            'reduction_value' => 'decimal:4', 'priority' => 'integer',
            'effective_from' => 'date', 'effective_to' => 'date',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentFeePolicy::class);
    }

    public function discountSnapshots(): HasMany
    {
        return $this->hasMany(FeeAssessmentDiscount::class);
    }
}
