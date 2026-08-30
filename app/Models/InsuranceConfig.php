<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceConfig extends Model
{
    protected $fillable = [
        'code', 'name', 'employee_rate_percentage', 'employer_rate_percentage',
        'salary_base_type', 'fixed_salary_base', 'maximum_salary_base',
        'effective_from', 'effective_to', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'employee_rate_percentage' => 'decimal:4', 'employer_rate_percentage' => 'decimal:4',
            'fixed_salary_base' => 'decimal:2', 'maximum_salary_base' => 'decimal:2',
            'effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean',
        ];
    }
}
