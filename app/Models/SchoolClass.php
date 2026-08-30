<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'homeroom_teacher_id',
        'code',
        'name',
        'room',
        'capacity',
        'status',
        'notes',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }
    public function assignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'class_id');
    }
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }
    public function feeAssessments(): HasMany
    {
        return $this->hasMany(FeeAssessment::class, 'class_id');
    }
    public function teachingExtraRecords(): HasMany
    {
        return $this->hasMany(TeachingExtraRecord::class, 'class_id');
    }
    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'class_id');
    }
}
