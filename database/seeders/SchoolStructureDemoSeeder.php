<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\HomeroomAssignmentService;
use App\Services\TeachingAssignmentService;
use Database\Seeders\Support\SchoolDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SchoolStructureDemoSeeder extends Seeder
{
    /** @var array<int, Collection<int, Teacher>> */
    private array $teacherPools = [];

    public function run(): void
    {
        $year = SchoolDemo::academicYear();
        $semester = SchoolDemo::semester($year);
        $subjects = Subject::where('is_active', true)->orderBy('id')->get();
        if ($subjects->isEmpty()) {
            throw new RuntimeException('Chưa có môn học đang hoạt động để tạo dữ liệu demo.');
        }

        $classes = $this->ensureClasses($year->id);
        $teachers = $this->createTeachers($subjects, $year->id);
        $this->assignHomeroomTeachers($classes, $teachers);
        $studentCount = $this->fillClassesWithStudents($classes, $year);
        $assignmentCount = $this->createTeachingAssignments($classes, $subjects, $year->id, $semester->id);

        $this->command?->info(sprintf(
            'Cấu trúc: %d lớp, %d giáo viên demo, %d học sinh trong các lớp, %d phân công mới.',
            $classes->count(),
            $teachers->count(),
            $studentCount,
            $assignmentCount
        ));
    }

    /** @return Collection<int, SchoolClass> */
    private function ensureClasses(int $academicYearId): Collection
    {
        $grades = Grade::whereIn('level', [10, 11, 12])->get()->keyBy('level');
        foreach ([10, 11, 12] as $level) {
            if (! $grades->has($level)) {
                throw new RuntimeException("Chưa có khối {$level}.");
            }
        }

        $existing = SchoolClass::where('academic_year_id', $academicYearId)
            ->get()
            ->keyBy(fn (SchoolClass $class) => strtoupper(preg_replace('/\s+/', '', $class->name)));
        $yearCode = SchoolDemo::academicYear()->code;

        foreach ([10, 11, 12] as $level) {
            for ($section = 1; $section <= 12; $section++) {
                $name = "{$level}A{$section}";
                if ($existing->has($name)) {
                    continue;
                }

                $class = SchoolClass::create([
                    'academic_year_id' => $academicYearId,
                    'grade_id' => $grades[$level]->id,
                    'code' => SchoolDemo::CLASS_CODE_PREFIX.$yearCode.'-'.$name,
                    'name' => $name,
                    'room' => sprintf('%s%02d', chr(65 + ($level - 10)), $section),
                    'capacity' => 40,
                    'status' => 'active',
                    'notes' => SchoolDemo::MARKER.' Lớp học mẫu.',
                ]);
                $existing->put($name, $class);
            }
        }

        return SchoolDemo::classes()->load('grade');
    }

    /**
     * @param  Collection<int, Subject>  $subjects
     * @return Collection<int, Teacher>
     */
    private function createTeachers(Collection $subjects, int $academicYearId): Collection
    {
        $roles = Role::whereIn('slug', ['teacher', 'subject_teacher'])->pluck('id', 'slug');
        if (! $roles->has('teacher') || ! $roles->has('subject_teacher')) {
            throw new RuntimeException('Thiếu role teacher hoặc subject_teacher. Hãy chạy DatabaseSeeder trước.');
        }

        $password = Hash::make(SchoolDemo::DEFAULT_PASSWORD);
        $globalIndex = 0;
        $allTeachers = collect();

        foreach ($subjects as $subject) {
            $periods = SchoolDemo::weeklyPeriods($subject->name);
            $poolSize = max(3, (int) ceil((36 * $periods) / 18));
            $pool = collect();

            for ($poolIndex = 1; $poolIndex <= $poolSize; $poolIndex++) {
                $globalIndex++;
                $female = $globalIndex % 2 === 0;
                $fullName = $this->teacherName($globalIndex, $female);
                $teacherCode = SchoolDemo::TEACHER_CODE_PREFIX.str_pad((string) $globalIndex, 3, '0', STR_PAD_LEFT);
                $email = sprintf('gv.%03d@%s', $globalIndex, SchoolDemo::DEMO_EMAIL_DOMAIN);

                $user = User::create([
                    'name' => $fullName,
                    'email' => $email,
                    'password' => $password,
                    'must_change_password' => false,
                ]);
                $user->roles()->syncWithoutDetaching($roles->values()->all());

                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'teacher_code' => $teacherCode,
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => '09'.str_pad((string) (70000000 + $globalIndex), 8, '0', STR_PAD_LEFT),
                    'date_of_birth' => sprintf('%d-%02d-%02d', 1978 + ($globalIndex % 17), (($globalIndex * 3) % 12) + 1, (($globalIndex * 7) % 27) + 1),
                    'gender' => $female ? 'female' : 'male',
                    'address' => 'Thành phố Hồ Chí Minh',
                    'hire_date' => sprintf('%d-08-15', 2010 + ($globalIndex % 13)),
                    'specialization' => $subject->name,
                    'status' => Teacher::STATUS_ACTIVE,
                    'notes' => SchoolDemo::MARKER.' Giáo viên mẫu môn '.$subject->name.'.',
                ]);

                $teacher->subjects()->attach($subject->id, [
                    'academic_year_id' => $academicYearId,
                    'is_primary' => true,
                ]);
                $pool->push($teacher);
                $allTeachers->push($teacher);
            }

            $this->teacherPools[$subject->id] = $pool;
        }

        return $allTeachers;
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @param  Collection<int, Teacher>  $teachers
     */
    private function assignHomeroomTeachers(Collection $classes, Collection $teachers): void
    {
        $homeroomRoleId = Role::where('slug', 'homeroom_teacher')->value('id');
        if (! $homeroomRoleId) {
            throw new RuntimeException('Thiếu role homeroom_teacher. Hãy chạy DatabaseSeeder trước.');
        }

        $usedTeacherIds = $classes->pluck('homeroom_teacher_id')->filter()->map(fn ($id) => (int) $id)->all();
        $available = $teachers->reject(fn (Teacher $teacher) => in_array($teacher->id, $usedTeacherIds, true))->values();
        $cursor = 0;
        $service = app(HomeroomAssignmentService::class);

        foreach ($classes as $class) {
            if ($class->homeroom_teacher_id) {
                continue;
            }
            $teacher = $available->get($cursor++);
            if (! $teacher) {
                throw new RuntimeException('Không đủ giáo viên để phân công chủ nhiệm không trùng lặp.');
            }

            $service->assign($class, $teacher);
            $teacher->user?->roles()->syncWithoutDetaching([$homeroomRoleId]);
        }
    }

    /** @param Collection<int, SchoolClass> $classes */
    private function fillClassesWithStudents(Collection $classes, $year): int
    {
        $studentRoleId = Role::where('slug', 'student')->value('id');
        if (! $studentRoleId) {
            throw new RuntimeException('Thiếu role student. Hãy chạy DatabaseSeeder trước.');
        }

        $password = Hash::make(SchoolDemo::DEFAULT_PASSWORD);
        $total = 0;
        $yearStart = (int) $year->starts_on->format('Y');

        foreach ($classes as $classIndex => $class) {
            $enrollments = Enrollment::where('academic_year_id', $year->id)
                ->where('class_id', $class->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->get();
            if ($enrollments->count() > 40) {
                $this->command?->warn("Lớp {$class->name} hiện có {$enrollments->count()} học sinh; seeder không xóa dữ liệu sẵn có.");
                $total += $enrollments->count();

                continue;
            }

            $usedRollNumbers = $enrollments->pluck('roll_number')->filter()->map(fn ($value) => (int) $value)->all();
            $needed = 40 - $enrollments->count();
            $positions = collect(range(1, 60))->reject(fn (int $position) => in_array($position, $usedRollNumbers, true))->take($needed);

            foreach ($positions as $position) {
                $classKey = strtolower(SchoolDemo::classKey($class));
                $studentCode = sprintf('%s%s-%03d', SchoolDemo::STUDENT_CODE_PREFIX, SchoolDemo::classKey($class), $position);
                $email = sprintf('hs.%s.%03d@%s', $classKey, $position, SchoolDemo::DEMO_EMAIL_DOMAIN);
                $female = ($position + $classIndex) % 2 === 0;
                $fullName = $this->studentName($position + ($classIndex * 40), $female);
                $birthYear = $yearStart - ($class->grade->level + 5);

                $user = User::create([
                    'name' => $fullName,
                    'email' => $email,
                    'password' => $password,
                    'must_change_password' => false,
                ]);
                $user->roles()->syncWithoutDetaching([$studentRoleId]);

                $student = Student::create([
                    'user_id' => $user->id,
                    'student_code' => $studentCode,
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => '03'.str_pad((string) (50000000 + ($classIndex * 100) + $position), 8, '0', STR_PAD_LEFT),
                    'date_of_birth' => sprintf('%d-%02d-%02d', $birthYear, (($position * 5) % 12) + 1, (($position * 7) % 27) + 1),
                    'gender' => $female ? 'female' : 'male',
                    'address' => 'Thành phố Hồ Chí Minh',
                    'birth_place' => 'Thành phố Hồ Chí Minh',
                    'admission_year' => ($yearStart - ($class->grade->level - 10)).'-'.($yearStart - ($class->grade->level - 10) + 1),
                    'status' => Student::STATUS_STUDYING,
                    'notes' => SchoolDemo::MARKER.' Hồ sơ học sinh mẫu.',
                ]);

                Enrollment::create([
                    'academic_year_id' => $year->id,
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                    'roll_number' => str_pad((string) $position, 2, '0', STR_PAD_LEFT),
                    'status' => Enrollment::STATUS_ACTIVE,
                    'enrolled_on' => $year->starts_on,
                    'notes' => SchoolDemo::MARKER.' Xếp lớp mẫu.',
                ]);

                $guardian = Guardian::create([
                    'full_name' => $female ? 'Nguyễn Văn Minh' : 'Trần Thị Lan',
                    'phone' => '08'.str_pad((string) (40000000 + ($classIndex * 100) + $position), 8, '0', STR_PAD_LEFT),
                    'email' => 'ph.'.$email,
                    'address' => 'Thành phố Hồ Chí Minh',
                    'occupation' => $position % 3 === 0 ? 'Kinh doanh' : 'Nhân viên văn phòng',
                    'workplace' => 'Doanh nghiệp tại địa phương',
                    'notes' => SchoolDemo::MARKER.' Phụ huynh mẫu.',
                ]);
                $student->guardians()->attach($guardian->id, [
                    'relationship' => $female ? 'father' : 'mother',
                    'is_primary' => true,
                ]);
            }

            $total += 40;
        }

        return $total;
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @param  Collection<int, Subject>  $subjects
     */
    private function createTeachingAssignments(Collection $classes, Collection $subjects, int $yearId, int $semesterId): int
    {
        $service = app(TeachingAssignmentService::class);
        $created = 0;

        foreach ($classes as $classIndex => $class) {
            foreach ($subjects as $subjectIndex => $subject) {
                $existing = TeachingAssignment::query()
                    ->where('academic_year_id', $yearId)
                    ->where('semester_id', $semesterId)
                    ->where('class_id', $class->id)
                    ->where('subject_id', $subject->id)
                    ->where('status', TeachingAssignment::STATUS_ACTIVE)
                    ->first();
                if ($existing) {
                    continue;
                }

                $pool = $this->teacherPools[$subject->id];
                $teacher = $pool[($classIndex + $subjectIndex) % $pool->count()];
                $service->create([
                    'academic_year_id' => $yearId,
                    'semester_id' => $semesterId,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'status' => TeachingAssignment::STATUS_ACTIVE,
                ]);
                $created++;
            }
        }

        return $created;
    }

    private function teacherName(int $index, bool $female): string
    {
        $surnames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng'];
        $middles = $female
            ? ['Thị', 'Ngọc', 'Thanh', 'Thu', 'Mai', 'Bích']
            : ['Văn', 'Hữu', 'Đức', 'Minh', 'Quang', 'Hoàng'];
        $givenNames = $female
            ? ['Anh', 'Hương', 'Lan', 'Linh', 'Mai', 'Nga', 'Nhung', 'Phương', 'Thảo', 'Trang', 'Uyên', 'Yến']
            : ['An', 'Bình', 'Cường', 'Dũng', 'Hải', 'Hùng', 'Khánh', 'Long', 'Minh', 'Nam', 'Phong', 'Tuấn'];

        return sprintf(
            '%s %s %s',
            $surnames[$index % count($surnames)],
            $middles[intdiv($index, count($surnames)) % count($middles)],
            $givenNames[intdiv($index, 3) % count($givenNames)]
        );
    }

    private function studentName(int $index, bool $female): string
    {
        $surnames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ'];
        $middles = $female ? ['Thị', 'Ngọc', 'Thu', 'Thanh'] : ['Văn', 'Đức', 'Minh', 'Hữu'];
        $givenNames = $female
            ? ['An', 'Anh', 'Chi', 'Hà', 'Hân', 'Hương', 'Lan', 'Linh', 'Mai', 'Nhi', 'Phương', 'Thảo', 'Trang', 'Vy', 'Yến']
            : ['An', 'Bảo', 'Bình', 'Duy', 'Hải', 'Hào', 'Hiếu', 'Hoàng', 'Huy', 'Khang', 'Khôi', 'Long', 'Minh', 'Nam', 'Phúc'];

        return sprintf(
            '%s %s %s',
            $surnames[$index % count($surnames)],
            $middles[intdiv($index, count($surnames)) % count($middles)],
            $givenNames[intdiv($index, 7) % count($givenNames)]
        );
    }
}
