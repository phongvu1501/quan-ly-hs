<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = ['academic_year_id', 'semester_id', 'subject_id', 'class_id', 'name', 'exam_type', 'exam_date', 'starts_at', 'ends_at', 'room', 'notes'];
    protected function casts(): array { return ['exam_date' => 'date']; }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function results(): HasMany { return $this->hasMany(ExamResult::class); }
    public function invigilations(): HasMany { return $this->hasMany(ExamInvigilation::class); }
}
