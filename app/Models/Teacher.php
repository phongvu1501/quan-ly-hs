<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_ON_LEAVE, self::STATUS_INACTIVE];

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Đang công tác',
        self::STATUS_ON_LEAVE => 'Tạm nghỉ',
        self::STATUS_INACTIVE => 'Ngừng công tác',
    ];

    protected $fillable = [
        'user_id', 'teacher_code', 'full_name', 'avatar_path', 'email', 'phone', 'date_of_birth',
        'gender', 'address', 'hire_date', 'specialization', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'hire_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (Teacher $teacher): void {
            if (blank($teacher->teacher_code)) {
                $teacher->teacher_code = static::generateCode();
            }
        });
    }

    private static function generateCode(): string
    {
        do {
            $code = 'GV'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('teacher_code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function homeroomClasses(): HasMany { return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id'); }
    public function assignments(): HasMany { return $this->hasMany(TeachingAssignment::class); }
    public function schedules(): HasMany { return $this->hasMany(Schedule::class); }
    public function examInvigilations(): HasMany { return $this->hasMany(ExamInvigilation::class); }
    public function subjects(): BelongsToMany { return $this->belongsToMany(Subject::class, 'teacher_subject')->withPivot(['academic_year_id', 'is_primary'])->withTimestamps(); }
    public function salaryProfiles(): HasMany { return $this->hasMany(TeacherSalaryProfile::class); }
    public function payrollInputs(): HasMany { return $this->hasMany(TeacherPayrollInput::class); }
    public function teachingExtraRecords(): HasMany { return $this->hasMany(TeachingExtraRecord::class); }
    public function payrolls(): HasMany { return $this->hasMany(Payroll::class); }
    public function payrollPayments(): HasMany { return $this->hasMany(PayrollPayment::class); }
    public function payrollAdjustments(): HasMany { return $this->hasMany(PayrollAdjustment::class); }
    public function scores(): HasMany { return $this->hasMany(Score::class); }
    public function comments(): HasMany { return $this->hasMany(TeacherComment::class); }
    public function homeroomAssignments(): HasMany { return $this->hasMany(HomeroomAssignment::class); }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
