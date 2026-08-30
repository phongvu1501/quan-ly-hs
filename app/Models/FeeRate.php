<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeRate extends Model
{
    protected $fillable = [
        'fee_category_id', 'academic_year_id', 'code', 'name', 'period_type',
        'amount', 'effective_from', 'effective_to', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(FeeNotice::class);
    }
}
