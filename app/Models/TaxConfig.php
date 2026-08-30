<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxConfig extends Model
{
    protected $fillable = [
        'code',
        'name',
        'tax_year',
        'personal_deduction',
        'dependent_deduction',
        'effective_from',
        'effective_to',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'tax_year' => 'integer',
            'personal_deduction' => 'decimal:2',
            'dependent_deduction' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(TaxBracket::class)->orderBy('sequence');
    }
}
