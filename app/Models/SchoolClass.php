<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    public static function naturalSort(Collection $classes): Collection
    {
        return $classes->sort(function ($left, $right): int {
            $leftKey = self::naturalNameKey((string) ($left->name ?: $left->code ?: ''));
            $rightKey = self::naturalNameKey((string) ($right->name ?: $right->code ?: ''));

            $maxIndex = max(count($leftKey), count($rightKey));
            for ($i = 0; $i < $maxIndex; $i++) {
                $leftValue = $leftKey[$i] ?? null;
                $rightValue = $rightKey[$i] ?? null;

                if ($leftValue === $rightValue) {
                    continue;
                }

                if ($leftValue === null) {
                    return -1;
                }

                if ($rightValue === null) {
                    return 1;
                }

                if (is_int($leftValue) && is_int($rightValue)) {
                    return $leftValue <=> $rightValue;
                }

                if (is_int($leftValue) !== is_int($rightValue)) {
                    return is_int($leftValue) ? -1 : 1;
                }

                return strcmp((string) $leftValue, (string) $rightValue);
            }

            return strcmp(
                (string) ($left->name ?: $left->code ?: ''),
                (string) ($right->name ?: $right->code ?: '')
            );
        })->values();
    }

    private static function naturalNameKey(string $name): array
    {
        preg_match_all('/[A-Za-z]+|\d+/', strtoupper($name), $matches);

        if (empty($matches[0])) {
            return [strtoupper($name)];
        }

        return array_map(function (string $token): int|string {
            return ctype_digit($token) ? (int) $token : strtolower($token);
        }, $matches[0]);
    }

    protected $table = 'classes';

    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'homeroom_teacher_id',
        'code',
        'name',
        'room',
        'capacity',
        'status',
        'notes',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }
    public function assignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'class_id');
    }
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }
    public function feeAssessments(): HasMany
    {
        return $this->hasMany(FeeAssessment::class, 'class_id');
    }
    public function teachingExtraRecords(): HasMany
    {
        return $this->hasMany(TeachingExtraRecord::class, 'class_id');
    }
    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'class_id');
    }
}
