<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends Model
{
    protected $fillable = ['code', 'name', 'type', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(FeeRate::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(FeePolicy::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(FeeNotice::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(FeeAssessment::class);
    }
}
