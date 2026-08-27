<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id', 'student_code', 'full_name', 'avatar_path', 'email', 'phone', 'date_of_birth',
        'gender', 'address', 'birth_place', 'admission_year', 'status', 'avatar', 'notes',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (Student $student): void {
            if (blank($student->student_code)) {
                $student->student_code = static::generateCode();
            }
        });
    }

    private static function generateCode(): string
    {
        do {
            $code = 'HS'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('student_code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
    public function guardians(): BelongsToMany { return $this->belongsToMany(Guardian::class)->withPivot(['relationship', 'is_primary']); }
    public function scores(): HasMany { return $this->hasMany(Score::class); }
    public function attendance(): HasMany { return $this->hasMany(AttendanceRecord::class); }
    public function comments(): HasMany { return $this->hasMany(TeacherComment::class); }
    public function payments(): HasMany { return $this->hasMany(FeePayment::class); }
    public function subjectResults(): HasMany { return $this->hasMany(SubjectResult::class); }
    public function examResults(): HasMany { return $this->hasMany(ExamResult::class); }
    public function conductEvaluations(): HasMany { return $this->hasMany(ConductEvaluation::class); }
}
