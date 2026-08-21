<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherComment extends Model
{
    protected $fillable = ['academic_year_id', 'semester_id', 'student_id', 'subject_id', 'teacher_id', 'content'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
}
