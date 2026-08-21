<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConductEvaluation extends Model
{
    protected $fillable = ['academic_year_id', 'semester_id', 'student_id', 'rating', 'comment', 'evaluated_by'];
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
