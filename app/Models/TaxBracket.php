<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxBracket extends Model
{
    protected $fillable = [
        'tax_config_id',
        'sequence',
        'lower_bound',
        'upper_bound',
        'rate_percentage',
        'quick_deduction',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'lower_bound' => 'decimal:2',
            'upper_bound' => 'decimal:2',
            'rate_percentage' => 'decimal:4',
            'quick_deduction' => 'decimal:2',
        ];
    }

    public function taxConfig(): BelongsTo
    {
        return $this->belongsTo(TaxConfig::class);
    }
}
