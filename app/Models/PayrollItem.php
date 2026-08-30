<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id',
        'salary_component_id',
        'line_key',
        'component_code',
        'component_name',
        'type',
        'category',
        'calculation_type',
        'quantity',
        'rate',
        'percentage_value',
        'calculation_base_amount',
        'amount',
        'is_taxable',
        'is_insurance_base',
        'source_type',
        'source_id',
        'subject_code_snapshot',
        'subject_name_snapshot',
        'class_code_snapshot',
        'class_name_snapshot',
        'description',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'percentage_value' => 'decimal:4',
            'calculation_base_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'is_taxable' => 'boolean',
            'is_insurance_base' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }
}
