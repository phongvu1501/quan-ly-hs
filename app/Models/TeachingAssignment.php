<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    protected $fillable = ['academic_year_id', 'semester_id', 'class_id', 'subject_id', 'teacher_id', 'status'];

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function scores(): HasMany { return $this->hasMany(Score::class, 'assignment_id'); }
}
