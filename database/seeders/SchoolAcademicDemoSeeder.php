<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamInvigilation;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Database\Seeders\Support\SchoolDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SchoolAcademicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $year = SchoolDemo::academicYear();
        $semester = SchoolDemo::semester($year);
        $classes = SchoolDemo::classes($year)->load('grade');
        $subjects = Subject::where('is_active', true)->orderBy('id')->get();
        $actor = SchoolDemo::actor();

        if ($classes->count() !== 36) {
            throw new RuntimeException('Cần đủ 36 lớp trước khi tạo hoạt động học vụ demo.');
        }

        $this->clearAcademicDemoRows();
        $scheduleCount = $this->createSchedules($classes, $subjects, $year->id, $semester->id);
        $announcementCount = $this->createAnnouncements($classes, $actor->id);
        $examCount = $this->createExams($classes, $subjects, $year, $semester);
        [$scoreCount, $resultCount] = $this->createScoresAndResults($classes, $subjects, $year, $semester, $actor->id);
        $attendanceCount = $this->createAttendanceAndComments($classes, $year, $semester, $actor->id);

        $this->command?->info(sprintf(
            'Học vụ: %d tiết học, %d thông báo, %d lịch thi, %d điểm, %d kết quả môn, %d lượt điểm danh.',
            $scheduleCount,
            $announcementCount,
            $examCount,
            $scoreCount,
            $resultCount,
            $attendanceCount
        ));
    }

    private function clearAcademicDemoRows(): void
    {
        DB::table('scores')->where('note', 'like', SchoolDemo::MARKER.'%')->delete();
        DB::table('subject_results')->where('teacher_comment', 'like', SchoolDemo::MARKER.'%')->delete();
        DB::table('teacher_comments')->where('content', 'like', SchoolDemo::MARKER.'%')->delete();
        DB::table('conduct_evaluations')->where('comment', 'like', SchoolDemo::MARKER.'%')->delete();
        DB::table('attendance_records')->where('reason', 'like', SchoolDemo::MARKER.'%')->delete();
        Schedule::where('notes', 'like', SchoolDemo::MARKER.'%')->delete();
        Announcement::where('title', 'like', SchoolDemo::MARKER.'%')->delete();
        Exam::where('notes', 'like', SchoolDemo::MARKER.'%')->delete();
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @param  Collection<int, Subject>  $subjects
     */
    private function createSchedules(Collection $classes, Collection $subjects, int $yearId, int $semesterId): int
    {
        $assignments = TeachingAssignment::with('teacher')
            ->where('academic_year_id', $yearId)
            ->where('semester_id', $semesterId)
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->whereIn('class_id', $classes->pluck('id'))
            ->get()
            ->groupBy(fn (TeachingAssignment $assignment) => $assignment->class_id.'|'.$assignment->subject_id)
            ->map->first();

        $existingSchedules = Schedule::where('semester_id', $semesterId)->get();
        $classBusy = [];
        $teacherBusy = [];
        $subjectDays = [];
        $classDayLoads = [];
        $teacherDayLoads = [];
        $existingCounts = [];

        foreach ($existingSchedules as $schedule) {
            $slot = $schedule->weekday.'|'.$schedule->period;
            $classBusy[$schedule->class_id][$slot] = true;
            if ($schedule->teacher_id) {
                $teacherBusy[$schedule->teacher_id][$slot] = true;
                $teacherDayLoads[$schedule->teacher_id][$schedule->weekday] = ($teacherDayLoads[$schedule->teacher_id][$schedule->weekday] ?? 0) + 1;
            }
            $subjectDays[$schedule->class_id][$schedule->subject_id][$schedule->weekday] = true;
            $classDayLoads[$schedule->class_id][$schedule->weekday] = ($classDayLoads[$schedule->class_id][$schedule->weekday] ?? 0) + 1;
            $key = $schedule->class_id.'|'.$schedule->subject_id;
            $existingCounts[$key] = ($existingCounts[$key] ?? 0) + 1;
        }

        $created = 0;
        $weekStart = today()->startOfWeek()->toDateString();
        foreach ($classes as $classIndex => $class) {
            $demands = [];
            foreach ($subjects as $subjectIndex => $subject) {
                $assignment = $assignments->get($class->id.'|'.$subject->id);
                if (! $assignment) {
                    throw new RuntimeException("Thiếu phân công {$subject->name} cho lớp {$class->name}.");
                }
                $required = max(0, SchoolDemo::weeklyPeriods($subject->name) - ($existingCounts[$class->id.'|'.$subject->id] ?? 0));
                for ($occurrence = 0; $occurrence < $required; $occurrence++) {
                    $demands[] = compact('subject', 'assignment', 'subjectIndex', 'occurrence');
                }
            }

            usort($demands, fn (array $left, array $right) => SchoolDemo::weeklyPeriods($right['subject']->name) <=> SchoolDemo::weeklyPeriods($left['subject']->name));
            $availableSlots = 30 - count($classBusy[$class->id] ?? []);
            if (count($demands) > $availableSlots) {
                $demands = array_slice($demands, 0, max(0, $availableSlots));
            }
            $placements = $this->matchScheduleSlots(
                $class,
                $demands,
                $classIndex,
                $classBusy,
                $teacherBusy,
                $subjectDays,
                $classDayLoads,
                $teacherDayLoads
            );
            foreach ($placements as $placement) {
                $demand = $placement['demand'];
                $subject = $demand['subject'];
                $assignment = $demand['assignment'];
                $slot = $placement['slot'];

                Schedule::create([
                    'academic_year_id' => $yearId,
                    'semester_id' => $semesterId,
                    'week_start' => $weekStart,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $assignment->teacher_id,
                    'weekday' => $slot['day'],
                    'period' => $slot['period'],
                    'room' => in_array($subject->name, ['Vật lý', 'Hóa học', 'Sinh học', 'Tin học'], true)
                        ? 'Phòng bộ môn'
                        : ($class->room ?: $class->name),
                    'notes' => SchoolDemo::MARKER.' Thời khóa biểu mẫu.',
                ]);

                $slotKey = $slot['day'].'|'.$slot['period'];
                $classBusy[$class->id][$slotKey] = true;
                $teacherBusy[$assignment->teacher_id][$slotKey] = true;
                $subjectDays[$class->id][$subject->id][$slot['day']] = true;
                $classDayLoads[$class->id][$slot['day']] = ($classDayLoads[$class->id][$slot['day']] ?? 0) + 1;
                $teacherDayLoads[$assignment->teacher_id][$slot['day']] = ($teacherDayLoads[$assignment->teacher_id][$slot['day']] ?? 0) + 1;
                $created++;
            }
        }

        return $created;
    }

    /**
     * @param  array<int, array<string, bool>>  $classBusy
     * @param  array<int, array<string, bool>>  $teacherBusy
     * @param  array<int, array<int, array<int, bool>>>  $subjectDays
     * @param  array<int, array<int, int>>  $classDayLoads
     * @param  array<int, array<int, int>>  $teacherDayLoads
     * @param  list<array<string, mixed>>  $demands
     * @return list<array{demand: array<string, mixed>, slot: array{day: int, period: int}}>
     */
    private function matchScheduleSlots(
        SchoolClass $class,
        array $demands,
        int $classIndex,
        array $classBusy,
        array $teacherBusy,
        array $subjectDays,
        array $classDayLoads,
        array $teacherDayLoads
    ): array {
        if ($demands === []) {
            return [];
        }

        $candidateSlots = [];
        foreach ($demands as $demandIndex => $demand) {
            $teacherId = $demand['assignment']->teacher_id;
            $subjectId = $demand['subject']->id;
            foreach (range(2, 6) as $day) {
                foreach (range(1, 6) as $period) {
                    $key = $day.'|'.$period;
                    if (isset($classBusy[$class->id][$key]) || isset($teacherBusy[$teacherId][$key])) {
                        continue;
                    }
                    $sameSubjectDay = isset($subjectDays[$class->id][$subjectId][$day]);
                    $score = (($classDayLoads[$class->id][$day] ?? 0) * 100)
                        + (($teacherDayLoads[$teacherId][$day] ?? 0) * 20)
                        + ($sameSubjectDay ? 200 : 0)
                        + (($day * 11 + $period * 7 + $classIndex * 5 + $demandIndex) % 17);
                    $candidateSlots[$demandIndex][] = compact('key', 'day', 'period', 'score');
                }
            }
            usort($candidateSlots[$demandIndex], fn (array $left, array $right) => $left['score'] <=> $right['score']);
        }

        $demandOrder = array_keys($demands);
        usort($demandOrder, fn (int $left, int $right) => count($candidateSlots[$left] ?? []) <=> count($candidateSlots[$right] ?? []));
        $slotToDemand = [];
        $demandToSlot = [];

        $augment = function (int $demandIndex, array &$seen) use (
            &$augment,
            &$slotToDemand,
            &$demandToSlot,
            $candidateSlots
        ): bool {
            foreach ($candidateSlots[$demandIndex] ?? [] as $slot) {
                if (isset($seen[$slot['key']])) {
                    continue;
                }
                $seen[$slot['key']] = true;
                $currentDemand = $slotToDemand[$slot['key']] ?? null;
                if ($currentDemand === null || $augment($currentDemand, $seen)) {
                    $slotToDemand[$slot['key']] = $demandIndex;
                    $demandToSlot[$demandIndex] = $slot;

                    return true;
                }
            }

            return false;
        };

        foreach ($demandOrder as $demandIndex) {
            $seen = [];
            if (! $augment($demandIndex, $seen)) {
                throw new RuntimeException("Không thể xếp thời khóa biểu không trùng giáo viên cho lớp {$class->name}.");
            }
        }

        $placements = [];
        foreach ($demands as $demandIndex => $demand) {
            $slot = $demandToSlot[$demandIndex];
            $placements[] = [
                'demand' => $demand,
                'slot' => ['day' => $slot['day'], 'period' => $slot['period']],
            ];
        }

        return $placements;
    }

    /** @param Collection<int, SchoolClass> $classes */
    private function createAnnouncements(Collection $classes, int $actorId): int
    {
        $announcements = [
            ['Kế hoạch năm học', 'Nhà trường công bố kế hoạch học tập, kiểm tra và hoạt động chung trong năm học.', 'general', 'all'],
            ['Thời khóa biểu chính thức', 'Thời khóa biểu tuần hiện tại đã được cập nhật. Học sinh và giáo viên kiểm tra trước khi đến lớp.', 'schedule', 'all'],
            ['Lịch kiểm tra giữa kỳ', 'Lịch kiểm tra giữa kỳ đã được công bố theo từng lớp và môn học.', 'exam', 'students'],
            ['Hướng dẫn sử dụng cổng học sinh', 'Học sinh sử dụng tài khoản được cấp để theo dõi lịch học, điểm và học phí.', 'guide', 'students'],
            ['Họp chuyên môn định kỳ', 'Giáo viên tham dự họp tổ chuyên môn vào cuối tuần theo kế hoạch.', 'meeting', 'teachers'],
            ['Nhắc hạn hoàn thành điểm', 'Giáo viên hoàn thành nhập điểm và nhận xét đúng thời hạn.', 'academic', 'teachers'],
        ];
        foreach ($announcements as $index => [$title, $content, $type, $audience]) {
            Announcement::create([
                'author_id' => $actorId,
                'title' => SchoolDemo::MARKER.' '.$title,
                'content' => $content,
                'type' => $type,
                'audience' => $audience,
                'published_at' => now()->subDays(6 - $index),
                'expires_at' => now()->addMonths(3),
                'is_published' => true,
            ]);
        }

        foreach ($classes as $index => $class) {
            Announcement::create([
                'author_id' => $actorId,
                'title' => SchoolDemo::MARKER.' Lịch học tuần - '.$class->name,
                'content' => "Lớp {$class->name} học theo thời khóa biểu đã công bố. Các thay đổi phòng học sẽ được cập nhật trên hệ thống.",
                'type' => 'schedule',
                'audience' => 'students',
                'class_id' => $class->id,
                'published_at' => now()->subHours($index % 24),
                'expires_at' => now()->addWeeks(4),
                'is_published' => true,
            ]);
        }

        return count($announcements) + $classes->count();
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @param  Collection<int, Subject>  $subjects
     */
    private function createExams(Collection $classes, Collection $subjects, $year, $semester): int
    {
        $examSubjects = $subjects->whereIn('name', ['Toán', 'Ngữ văn', 'Tiếng Anh'])->values();
        if ($examSubjects->count() < 3) {
            throw new RuntimeException('Cần đủ các môn Toán, Ngữ văn và Tiếng Anh để tạo lịch thi demo.');
        }
        $teachers = Teacher::where('teacher_code', 'like', SchoolDemo::TEACHER_CODE_PREFIX.'%')->orderBy('id')->get();
        $teacherBusy = [];
        $examCount = 0;

        foreach ($examSubjects as $subjectIndex => $subject) {
            $date = Carbon::parse($semester->starts_on)->addWeeks(8)->addDays($subjectIndex * 2);
            foreach ($classes as $classIndex => $class) {
                $shift = $classIndex % 3;
                $times = [['07:30:00', '09:00:00'], ['10:00:00', '11:30:00'], ['14:00:00', '15:30:00']][$shift];
                $exam = Exam::create([
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                    'subject_id' => $subject->id,
                    'class_id' => $class->id,
                    'name' => "Giữa kỳ {$semester->name} - {$subject->name} - {$class->name}",
                    'exam_type' => 'midterm',
                    'exam_date' => $date,
                    'starts_at' => $times[0],
                    'ends_at' => $times[1],
                    'room' => $class->room ?: $class->name,
                    'notes' => SchoolDemo::MARKER.' Lịch thi mẫu.',
                ]);

                $busyKey = $date->toDateString().'|'.$times[0];
                $available = $teachers->reject(fn (Teacher $teacher) => isset($teacherBusy[$busyKey][$teacher->id]))->values();
                foreach (['chief', 'invigilator'] as $roleIndex => $role) {
                    $teacher = $available->get(($classIndex * 2 + $roleIndex) % max(1, $available->count()));
                    if (! $teacher) {
                        throw new RuntimeException('Không đủ giáo viên coi thi không trùng ca.');
                    }
                    ExamInvigilation::create([
                        'exam_id' => $exam->id,
                        'teacher_id' => $teacher->id,
                        'role' => $role,
                        'notes' => SchoolDemo::MARKER.' Phân công coi thi mẫu.',
                    ]);
                    $teacherBusy[$busyKey][$teacher->id] = true;
                    $available = $available->reject(fn (Teacher $item) => $item->id === $teacher->id)->values();
                }
                $examCount++;
            }
        }

        $now = now();
        $resultRows = [];
        $exams = Exam::where('notes', 'like', SchoolDemo::MARKER.'%')->get();
        foreach ($exams as $exam) {
            $studentIds = Enrollment::where('academic_year_id', $year->id)
                ->where('class_id', $exam->class_id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->pluck('student_id');
            foreach ($studentIds as $studentId) {
                $resultRows[] = [
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'score' => SchoolDemo::score("exam|{$exam->id}|{$studentId}"),
                    'status' => 'present',
                    'note' => SchoolDemo::MARKER.' Kết quả thi mẫu.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        SchoolDemo::insertInChunks('exam_results', $resultRows);

        return $examCount;
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @param  Collection<int, Subject>  $subjects
     * @return array{int, int}
     */
    private function createScoresAndResults(Collection $classes, Collection $subjects, $year, $semester, int $actorId): array
    {
        $assignments = TeachingAssignment::with('teacher')
            ->where('academic_year_id', $year->id)
            ->where('semester_id', $semester->id)
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->whereIn('class_id', $classes->pluck('id'))
            ->get()
            ->groupBy(fn (TeachingAssignment $assignment) => $assignment->class_id.'|'.$assignment->subject_id)
            ->map->first();
        $scoreTypes = ['oral', 'fifteen_minute', 'midterm', 'final'];
        $scoreDates = [14, 35, 63, 91];
        $now = now();
        $scoreRows = [];
        $resultRows = [];

        foreach ($classes as $class) {
            $studentIds = Enrollment::where('academic_year_id', $year->id)
                ->where('class_id', $class->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->pluck('student_id');
            foreach ($studentIds as $studentId) {
                foreach ($subjects as $subject) {
                    $assignment = $assignments->get($class->id.'|'.$subject->id);
                    if (! $assignment) {
                        continue;
                    }
                    $scores = [];
                    foreach ($scoreTypes as $typeIndex => $scoreType) {
                        $score = SchoolDemo::score("score|{$studentId}|{$subject->id}|{$scoreType}");
                        $scores[] = $score;
                        $scoreRows[] = [
                            'academic_year_id' => $year->id,
                            'semester_id' => $semester->id,
                            'student_id' => $studentId,
                            'class_id' => $class->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $assignment->teacher_id,
                            'assignment_id' => $assignment->id,
                            'score_type' => $scoreType,
                            'score' => $score,
                            'assessed_on' => Carbon::parse($semester->starts_on)->addDays($scoreDates[$typeIndex])->toDateString(),
                            'note' => SchoolDemo::MARKER.' Điểm mẫu.',
                            'entered_by' => $assignment->teacher?->user_id ?: $actorId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    $average = round(array_sum($scores) / count($scores), 2);
                    $resultRows[] = [
                        'academic_year_id' => $year->id,
                        'semester_id' => $semester->id,
                        'student_id' => $studentId,
                        'subject_id' => $subject->id,
                        'average_score' => $average,
                        'rating' => $average >= 8 ? 'Tốt' : ($average >= 6.5 ? 'Khá' : ($average >= 5 ? 'Đạt' : 'Cần cố gắng')),
                        'teacher_comment' => SchoolDemo::MARKER.' Kết quả học tập mẫu.',
                        'calculated_by' => $actorId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        SchoolDemo::insertInChunks('scores', $scoreRows);
        foreach (array_chunk($resultRows, 800) as $chunk) {
            DB::table('subject_results')->insertOrIgnore($chunk);
        }

        return [count($scoreRows), count($resultRows)];
    }

    /** @param Collection<int, SchoolClass> $classes */
    private function createAttendanceAndComments(Collection $classes, $year, $semester, int $actorId): int
    {
        $attendanceDates = collect();
        $cursor = today()->copy();
        while ($attendanceDates->count() < 10 && $cursor->gte(Carbon::parse($semester->starts_on))) {
            if ($cursor->isWeekday()) {
                $attendanceDates->prepend($cursor->toDateString());
            }
            $cursor->subDay();
        }
        while ($attendanceDates->count() < 10) {
            $next = Carbon::parse($semester->starts_on)->addWeekdays($attendanceDates->count());
            $attendanceDates->push($next->toDateString());
        }

        $now = now();
        $attendanceRows = [];
        $conductRows = [];
        $commentRows = [];

        foreach ($classes as $class) {
            $studentIds = Enrollment::where('academic_year_id', $year->id)
                ->where('class_id', $class->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->pluck('student_id');
            $homeroomTeacher = $class->homeroomTeacher;
            foreach ($studentIds as $studentId) {
                foreach ($attendanceDates as $date) {
                    $roll = SchoolDemo::deterministicNumber("attendance|{$studentId}|{$date}", 1, 100);
                    $status = $roll <= 88 ? 'present' : ($roll <= 93 ? 'late' : ($roll <= 97 ? 'excused' : 'absent'));
                    $attendanceRows[] = [
                        'academic_year_id' => $year->id,
                        'class_id' => $class->id,
                        'student_id' => $studentId,
                        'subject_id' => null,
                        'attended_on' => $date,
                        'period' => null,
                        'status' => $status,
                        'reason' => SchoolDemo::MARKER.($status === 'present' ? ' Điểm danh mẫu.' : ' Lý do mẫu đã được ghi nhận.'),
                        'recorded_by' => $homeroomTeacher?->user_id ?: $actorId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                $average = SchoolDemo::score("conduct|{$studentId}");
                $conductRows[] = [
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                    'student_id' => $studentId,
                    'rating' => $average >= 8 ? 'Tốt' : ($average >= 6.5 ? 'Khá' : 'Đạt'),
                    'comment' => SchoolDemo::MARKER.' Có ý thức học tập và tham gia hoạt động lớp.',
                    'evaluated_by' => $homeroomTeacher?->user_id ?: $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($homeroomTeacher) {
                    $commentRows[] = [
                        'academic_year_id' => $year->id,
                        'semester_id' => $semester->id,
                        'student_id' => $studentId,
                        'subject_id' => null,
                        'teacher_id' => $homeroomTeacher->id,
                        'content' => SchoolDemo::MARKER.' Học sinh có tiến bộ và cần tiếp tục duy trì nề nếp học tập.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        SchoolDemo::insertInChunks('attendance_records', $attendanceRows);
        foreach (array_chunk($conductRows, 800) as $chunk) {
            DB::table('conduct_evaluations')->insertOrIgnore($chunk);
        }
        SchoolDemo::insertInChunks('teacher_comments', $commentRows);

        return count($attendanceRows);
    }
}
