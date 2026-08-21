<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'student_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'assignment_id',
        'score_type',
        'score',
        'assessed_on',
        'note',
        'entered_by',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'assessed_on' => 'date'];
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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
}
