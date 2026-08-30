<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_WITHDRAWN,
    ];

    protected $fillable = ['academic_year_id', 'class_id', 'student_id', 'roll_number', 'status', 'enrolled_on', 'left_on', 'notes'];

    protected function casts(): array
    {
        return ['enrolled_on' => 'date', 'left_on' => 'date'];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
