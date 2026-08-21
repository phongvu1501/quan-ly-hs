<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'user_id', 'teacher_code', 'full_name', 'avatar_path', 'email', 'phone', 'date_of_birth',
        'gender', 'address', 'hire_date', 'specialization', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'hire_date' => 'date'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function homeroomClasses(): HasMany { return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id'); }
    public function assignments(): HasMany { return $this->hasMany(TeachingAssignment::class); }
    public function schedules(): HasMany { return $this->hasMany(Schedule::class); }
    public function examInvigilations(): HasMany { return $this->hasMany(ExamInvigilation::class); }
    public function subjects(): BelongsToMany { return $this->belongsToMany(Subject::class, 'teacher_subject')->withPivot(['academic_year_id', 'is_primary'])->withTimestamps(); }
}
