<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    public const TYPES = ['earning', 'deduction'];

    public const CALCULATION_TYPES = ['fixed', 'percentage', 'per_unit', 'manual'];

    protected $fillable = [
        'code',
        'name',
        'type',
        'category',
        'calculation_type',
        'is_taxable',
        'is_insurance_base',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return ['is_taxable' => 'boolean', 'is_insurance_base' => 'boolean', 'is_active' => 'boolean'];
    }

    public function salaryConfigs(): HasMany
    {
        return $this->hasMany(TeacherSalaryConfig::class);
    }

    public function payrollInputs(): HasMany
    {
        return $this->hasMany(TeacherPayrollInput::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
