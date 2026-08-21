<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectResult extends Model
{
    protected $fillable = ['academic_year_id', 'semester_id', 'student_id', 'subject_id', 'average_score', 'rating', 'teacher_comment', 'calculated_by'];
    protected function casts(): array { return ['average_score' => 'decimal:2']; }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
}
