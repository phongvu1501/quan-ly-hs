<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = ['code', 'name', 'short_name', 'weekly_periods', 'is_active', 'description'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assignments(): HasMany { return $this->hasMany(TeachingAssignment::class); }
    public function teachers(): BelongsToMany { return $this->belongsToMany(Teacher::class, 'teacher_subject')->withPivot(['academic_year_id', 'is_primary'])->withTimestamps(); }
    public function schedules(): HasMany { return $this->hasMany(Schedule::class); }
}
