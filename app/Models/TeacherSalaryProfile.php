<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSalaryProfile extends Model
{
    protected $fillable = [
        'profile_code',
        'teacher_id',
        'effective_from',
        'effective_to',
        'dependent_count',
        'tax_enabled',
        'insurance_enabled',
        'status',
        'note',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'dependent_count' => 'integer',
            'tax_enabled' => 'boolean',
            'insurance_enabled' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function configs(): HasMany
    {
        return $this->hasMany(TeacherSalaryConfig::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
