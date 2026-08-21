<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = ['academic_year_id', 'class_id', 'student_id', 'roll_number', 'status', 'enrolled_on', 'left_on', 'notes'];

    protected function casts(): array
    {
        return ['enrolled_on' => 'date', 'left_on' => 'date'];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
