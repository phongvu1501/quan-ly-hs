<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicRecord extends Model
{
    protected $fillable = [
        'menu_id',
        'title',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
