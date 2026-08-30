<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeAssessmentDiscount extends Model
{
    protected $fillable = [
        'fee_assessment_id',
        'fee_policy_id',
        'sequence',
        'policy_code',
        'policy_name',
        'reduction_type',
        'reduction_value',
        'application_mode',
        'priority',
        'policy_effective_from',
        'policy_effective_to',
        'calculated_discount',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'priority' => 'integer',
            'reduction_value' => 'decimal:4',
            'policy_effective_from' => 'date',
            'policy_effective_to' => 'date',
            'calculated_discount' => 'decimal:2',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(FeeAssessment::class, 'fee_assessment_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(FeePolicy::class, 'fee_policy_id');
    }
}
