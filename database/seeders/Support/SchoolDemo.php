<?php

namespace Database\Seeders\Support;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SchoolDemo
{
    public const MARKER = '[SCHOOL_DEMO]';

    public const CLASS_CODE_PREFIX = 'DEMO-CLS-';

    public const STUDENT_CODE_PREFIX = 'DEMO-HS-';

    public const TEACHER_CODE_PREFIX = 'DEMO-GV-';

    public const FEE_CODE_PREFIX = 'SCHOOL-DEMO-FEE-';

    public const SALARY_PROFILE_PREFIX = 'SCHOOL-DEMO-SP-';

    public const PAYROLL_CODE_PREFIX = 'SCHOOL-DEMO-PR-';

    public const PAYROLL_INPUT_PREFIX = 'SCHOOL-DEMO-PI-';

    public const DEMO_EMAIL_DOMAIN = 'demo-school.test';

    public const DEFAULT_PASSWORD = 'Demo@123';

    /** @var array<string, int> */
    public const WEEKLY_PERIODS = [
        'Toán' => 4,
        'Ngữ văn' => 4,
        'Tiếng Anh' => 3,
        'Vật lý' => 2,
        'Hóa học' => 2,
        'Sinh học' => 2,
        'Lịch sử' => 2,
        'Địa lí' => 2,
        'Giáo dục công dân' => 1,
        'Tin học' => 2,
        'Công nghệ' => 2,
        'Thể dục' => 2,
        'Giáo dục quốc phòng' => 2,
    ];

    public static function academicYear(): AcademicYear
    {
        return AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::latest('starts_on')->first()
            ?? throw new RuntimeException('Chưa có năm học để tạo dữ liệu demo.');
    }

    public static function semester(AcademicYear $year): Semester
    {
        return $year->semesters()->where('is_current', true)->first()
            ?? $year->semesters()->orderBy('number')->first()
            ?? throw new RuntimeException("Năm học {$year->name} chưa có học kỳ.");
    }

    public static function actor(): User
    {
        return User::whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->first()
            ?? User::first()
            ?? throw new RuntimeException('Chưa có tài khoản quản trị để ghi nhận dữ liệu demo.');
    }

    /** @return Collection<int, SchoolClass> */
    public static function classes(?AcademicYear $year = null): Collection
    {
        $year ??= self::academicYear();

        return SchoolClass::with('grade')
            ->where('academic_year_id', $year->id)
            ->whereHas('grade', fn ($query) => $query->whereIn('level', [10, 11, 12]))
            ->get()
            ->filter(fn (SchoolClass $class) => self::isTargetClassName($class->name))
            ->sortBy(fn (SchoolClass $class) => sprintf('%02d-%02d', $class->grade->level, self::classSection($class->name)))
            ->values();
    }

    public static function classKey(SchoolClass $class): string
    {
        return strtoupper(preg_replace('/\s+/', '', $class->name));
    }

    public static function isTargetClassName(string $name): bool
    {
        return preg_match('/^(10|11|12)A(?:[1-9]|1[0-2])$/i', preg_replace('/\s+/', '', $name)) === 1;
    }

    public static function classSection(string $name): int
    {
        preg_match('/A(\d+)$/i', preg_replace('/\s+/', '', $name), $matches);

        return (int) ($matches[1] ?? 0);
    }

    public static function weeklyPeriods(string $subjectName): int
    {
        return self::WEEKLY_PERIODS[$subjectName] ?? 2;
    }

    public static function deterministicNumber(string $key, int $minimum, int $maximum): int
    {
        $range = $maximum - $minimum + 1;

        return $minimum + (abs(crc32($key)) % $range);
    }

    public static function score(string $key): float
    {
        return round(self::deterministicNumber($key, 45, 100) / 10, 1);
    }

    /** @param list<array<string, mixed>> $rows */
    public static function insertInChunks(string $table, array $rows, int $size = 800): void
    {
        foreach (array_chunk($rows, $size) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
