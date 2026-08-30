<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSalaryConfig extends Model
{
    protected $fillable = [
        'teacher_salary_profile_id',
        'salary_component_id',
        'subject_id',
        'scope_key',
        'fixed_amount',
        'percentage_value',
        'rate_per_unit',
        'default_quantity',
        'calculation_base',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'fixed_amount' => 'decimal:2',
            'percentage_value' => 'decimal:4',
            'rate_per_unit' => 'decimal:2',
            'default_quantity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(TeacherSalaryProfile::class, 'teacher_salary_profile_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teachingExtraRecords(): HasMany
    {
        return $this->hasMany(TeachingExtraRecord::class);
    }
}
