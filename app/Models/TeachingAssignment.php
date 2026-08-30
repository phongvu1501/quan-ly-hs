<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    protected $fillable = ['academic_year_id', 'semester_id', 'semester_scope_id', 'class_id', 'subject_id', 'teacher_id', 'status'];

    protected static function booted(): void
    {
        static::saving(function (TeachingAssignment $assignment): void {
            $assignment->semester_scope_id = (int) ($assignment->semester_id ?? 0);
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'assignment_id');
    }
    public function teachingExtraRecords(): HasMany
    {
        return $this->hasMany(TeachingExtraRecord::class);
    }
}
