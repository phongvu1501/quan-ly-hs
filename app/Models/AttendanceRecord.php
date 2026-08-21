<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = ['academic_year_id', 'class_id', 'student_id', 'subject_id', 'attended_on', 'period', 'status', 'reason', 'recorded_by'];
    protected function casts(): array
    {
        return ['attended_on' => 'date'];
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
}
