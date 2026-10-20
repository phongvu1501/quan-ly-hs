<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\Schedule;
use App\Models\Score;
use App\Models\Semester;
use App\Models\Student;
use App\Models\TeacherComment;
use App\Notifications\ExamInvigilationAssigned;
use Illuminate\Http\Request;

class TeacherPortalController extends Controller
{
    public function dashboard()
    {
        $teacher = $this->teacher();

        $year = $this->currentYear();
        $assignments = $teacher->assignments()
            ->with(['class.grade', 'subject', 'semester'])
            ->withCount('scores')
            ->where('academic_year_id', $year?->id)
            ->where('status', 'active')
            ->get();
        $semester = $this->currentSemester($year);
        $schedule = $teacher->schedules()
            ->with(['class', 'subject', 'semester'])
            ->where('academic_year_id', $year?->id)
            ->when($semester, fn($query) => $query->where('semester_id', $semester->id))
            ->orderBy('weekday')
            ->orderBy('period')
            ->get();
        $classIds = $assignments->pluck('class_id')->merge(
            $teacher->homeroomClasses()->where('academic_year_id', $year?->id)->pluck('id')
        )->unique()->values();
        $announcements = $this->teacherAnnouncements($classIds);
        $upcomingExams = $this->teacherExams($year, $semester, $classIds, $assignments);
        $invigilationNotifications = $this->teacherInvigilationNotifications();

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'year' => $year,
            'semester' => $semester,
            'assignments' => $assignments,
            'homeroomClasses' => $teacher->homeroomClasses()->with('grade')->withCount('enrollments')->where('academic_year_id', $year?->id)->get(),
            'students' => $assignments->pluck('class')->filter()->flatMap(fn($class) => $class->enrollments)->unique('student_id')->count(),
            'schedule' => $schedule,
            'announcements' => $announcements,
            'upcomingExams' => $upcomingExams,
            'invigilationNotifications' => $invigilationNotifications,
        ]);
    }

    public function homeroomDashboard()
    {
        $teacher = $this->teacher();
        $year = $this->currentYear();
        $classes = $teacher->homeroomClasses()
            ->with(['grade', 'enrollments.student'])
            ->withCount('enrollments')
            ->where('academic_year_id', $year?->id)
            ->get();
        $semester = $this->currentSemester($year);
        $classIds = $classes->pluck('id');
        $schedule = Schedule::with(['class', 'subject', 'teacher'])
            ->whereIn('class_id', $classIds)
            ->when($semester, fn($query) => $query->where('semester_id', $semester->id))
            ->orderBy('weekday')
            ->orderBy('period')
            ->get();
        $announcements = $this->teacherAnnouncements($classIds);
        $upcomingExams = $this->teacherExams($year, $semester, $classIds);
        $invigilationNotifications = $this->teacherInvigilationNotifications();
        $todayAttendance = AttendanceRecord::whereIn('class_id', $classIds)
            ->whereDate('attended_on', today())
            ->whereNull('subject_id')
            ->get()
            ->groupBy('status');

        return view('teacher.homeroom', [
            'teacher' => $teacher,
            'year' => $year,
            'semester' => $semester,
            'classes' => $classes,
            'studentCount' => $classes->sum('enrollments_count'),
            'schedule' => $schedule,
            'announcements' => $announcements,
            'upcomingExams' => $upcomingExams,
            'invigilationNotifications' => $invigilationNotifications,
            'todayAttendance' => $todayAttendance,
        ]);
    }

    public function subjectDashboard()
    {
        $teacher = $this->teacher();
        $year = $this->currentYear();
        $assignments = $teacher->assignments()
            ->with(['class.grade', 'subject', 'semester'])
            ->withCount('scores')
            ->where('academic_year_id', $year?->id)
            ->where('status', 'active')
            ->get();
        $semester = $this->currentSemester($year);
        $schedule = $teacher->schedules()
            ->with(['class', 'subject'])
            ->where('academic_year_id', $year?->id)
            ->when($semester, fn($query) => $query->where('semester_id', $semester->id))
            ->orderBy('weekday')
            ->orderBy('period')
            ->get();
        $classIds = $assignments->pluck('class_id')->unique()->values();
        $announcements = $this->teacherAnnouncements($classIds);
        $upcomingExams = $this->teacherExams($year, $semester, $classIds, $assignments);
        $invigilationNotifications = $this->teacherInvigilationNotifications();
        $studentCount = $assignments->pluck('class')->filter()->flatMap(fn($class) => $class->enrollments)->unique('student_id')->count();

        return view('teacher.subject', compact('teacher', 'year', 'semester', 'assignments', 'schedule', 'announcements', 'upcomingExams', 'studentCount', 'invigilationNotifications'));
    }

    public function scores(Request $request)
    {
        $teacher = $this->teacher();
        $year = $this->currentYear();
        $assignments = $teacher->assignments()->with(['class.grade', 'subject', 'semester'])->where('academic_year_id', $year?->id)->where('status', 'active')->get();
        $assignment = $assignments->firstWhere('id', (int) $request->assignment_id) ?: $assignments->first();
        $studentQuery = $assignment?->class()->with(['enrollments' => fn($query) => $query
            ->with('student')->where('status', 'active')
            ->whereHas('student', fn($student) => $student->where('status', Student::STATUS_STUDYING))])
            ->first()?->enrollments();
        $allStudents = $studentQuery?->with('student')->where('status', 'active')
            ->whereHas('student', fn($student) => $student->where('status', Student::STATUS_STUDYING))
            ->orderBy('roll_number')->get() ?? collect();
        $students = $studentQuery?->with('student')->where('status', 'active')
            ->whereHas('student', fn($student) => $student->where('status', Student::STATUS_STUDYING))
            ->orderBy('roll_number')->paginate(15)->withQueryString() ?? collect();
        $semesterId = $assignment?->semester_id ?: $this->currentSemester($year)?->id;

        $scoreMap = $assignment ? Score::where('assignment_id', $assignment->id)
            ->when($semesterId, fn($query) => $query->where('semester_id', $semesterId))
            ->whereIn('student_id', $allStudents->pluck('student_id'))
            ->latest('assessed_on')->latest()->get()->groupBy('student_id') : collect();

        $classScores = $assignment ? Score::where('academic_year_id', $year?->id)
            ->where('class_id', $assignment->class_id)
            ->when($semesterId, fn($query) => $query->where('semester_id', $semesterId))
            ->get() : collect();
        $academicSummaries = $this->academicSummaries($classScores);
        $subjectAverages = $scoreMap->map(fn($scores) => $this->weightedAverage($scores));
        $classAverage = $academicSummaries->pluck('average')->filter(fn($average) => $average !== null)->avg();
        $topStudentId = $academicSummaries->filter(fn($summary) => $summary['average'] !== null)->sortByDesc('average')->keys()->first();
        $topStudent = $allStudents->firstWhere('student_id', $topStudentId)?->student;

        return view('teacher.scores', compact(
            'teacher',
            'assignments',
            'assignment',
            'students',
            'scoreMap',
            'subjectAverages',
            'academicSummaries',
            'classAverage',
            'topStudent',
            'semesterId'
        ));
    }

    public function markNotificationRead(string $notification)
    {
        $record = auth()->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return back()->with('success', 'Đã đánh dấu thông báo là đã đọc.');
    }

    public function storeScore(Request $request)
    {
        $teacher = $this->teacher();
        if ($request->has('scores')) {
            return $this->storeScoreBatch($request, $teacher);
        }

        $data = $request->validate([
            'assignment_id' => ['required', 'exists:teaching_assignments,id'],
            'student_id' => ['required', 'exists:students,id'],
            'score_type' => ['required', 'string', 'max:30'],
            'score' => ['required', 'numeric', 'min:0', 'max:10'],
            'assessed_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $assignment = $teacher->assignments()->with(['class', 'subject', 'semester'])
            ->where('status', 'active')
            ->findOrFail($data['assignment_id']);
        abort_unless($assignment->academic_year_id === $this->currentYear()?->id, 403);
        abort_unless($assignment->class->enrollments()
            ->where('student_id', $data['student_id'])
            ->where('status', 'active')
            ->whereHas('student', fn($query) => $query->where('status', Student::STATUS_STUDYING))
            ->exists(), 422, 'Học sinh không ở trạng thái đang học hoặc không thuộc lớp được phân công.');

        $semesterId = $assignment->semester_id ?: Semester::where('academic_year_id', $assignment->academic_year_id)
            ->where('is_current', true)
            ->value('id');
        abort_unless($semesterId, 422, 'Không có học kỳ đang hoạt động cho phân công này.');

        Score::create([
            ...$data,
            'academic_year_id' => $assignment->academic_year_id,
            'semester_id' => $semesterId,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
            'teacher_id' => $teacher->id,
            'entered_by' => auth()->id(),
        ]);

        return back()->with('success', 'Đã lưu điểm.');
    }

    private function storeScoreBatch(Request $request, $teacher)
    {
        $data = $request->validate([
            'assignment_id' => ['required', 'exists:teaching_assignments,id'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.score_type' => ['required', 'string', 'max:30'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'scores.*.assessed_on' => ['nullable', 'date'],
            'scores.*.note' => ['nullable', 'string'],
        ]);

        $assignment = $teacher->assignments()->with(['class', 'subject', 'semester'])
            ->where('status', 'active')
            ->findOrFail($data['assignment_id']);
        abort_unless($assignment->academic_year_id === $this->currentYear()?->id, 403);

        $studentIds = $assignment->class->enrollments()
            ->where('status', 'active')
            ->whereHas('student', fn($query) => $query->where('status', Student::STATUS_STUDYING))
            ->pluck('student_id')
            ->map(fn($id) => (int) $id);
        $semesterId = $assignment->semester_id ?: Semester::where('academic_year_id', $assignment->academic_year_id)
            ->where('is_current', true)
            ->value('id');
        abort_unless($semesterId, 422, 'Không có học kỳ đang hoạt động cho phân công này.');

        $saved = 0;
        foreach ($data['scores'] as $rowKey => $row) {
            $studentId = (int) explode('_', (string) $rowKey, 2)[0];
            if (! array_key_exists('score', $row) || $row['score'] === null || $row['score'] === '') {
                continue;
            }
            abort_unless($studentIds->contains($studentId), 422, 'Học sinh không thuộc lớp được phân công.');

            $attributes = [
                'academic_year_id' => $assignment->academic_year_id,
                'semester_id' => $semesterId,
                'class_id' => $assignment->class_id,
                'subject_id' => $assignment->subject_id,
                'teacher_id' => $teacher->id,
                'score_type' => $row['score_type'],
                'score' => $row['score'],
                'assessed_on' => $row['assessed_on'] ?? null,
                'note' => $row['note'] ?? null,
                'entered_by' => auth()->id(),
            ];
            Score::create($attributes + ['student_id' => $studentId, 'assignment_id' => $assignment->id]);
            $saved++;
        }

        return back()->with('success', "Đã lưu {$saved} điểm.");
    }

    public function updateScore(Request $request, Score $score)
    {
        $teacher = $this->teacher();
        $assignment = $teacher->assignments()->with('class')
            ->where('status', 'active')
            ->findOrFail($score->assignment_id);
        abort_unless((int) $assignment->academic_year_id === (int) $this->currentYear()?->id, 403);
        abort_unless($assignment->class->enrollments()
            ->where('student_id', $score->student_id)
            ->where('status', 'active')
            ->whereHas('student', fn($query) => $query->where('status', Student::STATUS_STUDYING))
            ->exists(), 422, 'Học sinh không ở trạng thái đang học hoặc không thuộc lớp được phân công.');

        $data = $request->validate([
            'score_type' => ['required', 'string', 'max:30'],
            'score' => ['required', 'numeric', 'min:0', 'max:10'],
            'assessed_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $score->update($data + ['entered_by' => auth()->id()]);

        return back()->with('success', 'Đã cập nhật điểm.');
    }

    public function attendance(Request $request)
    {
        $teacher = $this->teacher();
        $year = $this->currentYear();
        $assignments = $teacher->assignments()->with(['class.grade', 'subject'])->where('academic_year_id', $year?->id)->where('status', 'active')->get();
        $classes = $teacher->homeroomClasses()->with('grade')->where('academic_year_id', $year?->id)->get()->merge($assignments->pluck('class'))->unique('id')->values();
        $class = $classes->firstWhere('id', (int) $request->class_id) ?: $classes->first();
        $date = $request->date('date') ?: today();
        $students = $class?->enrollments()->with('student')->where('status', 'active')
            ->whereHas('student', fn($query) => $query->where('status', Student::STATUS_STUDYING))
            ->orderBy('roll_number')->paginate(15)->withQueryString() ?? collect();
        $records = $class ? AttendanceRecord::where('class_id', $class->id)->whereDate('attended_on', $date)->get()->keyBy('student_id') : collect();

        return view('teacher.attendance', compact('teacher', 'classes', 'class', 'date', 'students', 'records'));
    }

    public function storeAttendance(Request $request)
    {
        $teacher = $this->teacher();
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'attended_on' => ['required', 'date'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', 'string', 'in:present,absent,late,excused'],
            'reasons' => ['nullable', 'array'],
        ]);
        $class = $teacher->homeroomClasses()
            ->where('academic_year_id', $this->currentYear()?->id)
            ->find($data['class_id'])
            ?: $teacher->assignments()
            ->where('academic_year_id', $this->currentYear()?->id)
            ->where('status', 'active')
            ->where('class_id', $data['class_id'])
            ->firstOrFail()
            ->class;

        foreach ($data['statuses'] as $studentId => $status) {
            abort_unless(
                $class->enrollments()->where('student_id', $studentId)->where('status', 'active')
                    ->whereHas('student', fn($query) => $query->where('status', Student::STATUS_STUDYING))->exists(),
                422,
                'Học sinh không ở trạng thái đang học hoặc không thuộc lớp được phân công.'
            );
            AttendanceRecord::firstOrCreate(
                ['class_id' => $class->id, 'student_id' => $studentId, 'attended_on' => $data['attended_on'], 'period' => null, 'subject_id' => null],
                ['academic_year_id' => $this->currentYear()?->id, 'status' => $status, 'reason' => $data['reasons'][$studentId] ?? null, 'recorded_by' => auth()->id()]
            );
        }

        return back()->with('success', 'Đã lưu điểm danh.');
    }

    public function updateAttendance(Request $request, AttendanceRecord $attendance)
    {
        $teacher = $this->teacher();
        $class = $teacher->homeroomClasses()
            ->where('academic_year_id', $this->currentYear()?->id)
            ->find($attendance->class_id)
            ?: $teacher->assignments()
            ->where('academic_year_id', $this->currentYear()?->id)
            ->where('status', 'active')
            ->where('class_id', $attendance->class_id)
            ->firstOrFail()
            ->class;
        abort_unless(
            $class->enrollments()->where('student_id', $attendance->student_id)->where('status', 'active')
                ->whereHas('student', fn($query) => $query->where('status', Student::STATUS_STUDYING))->exists(),
            422,
            'Học sinh không ở trạng thái đang học hoặc không thuộc lớp được phân công.'
        );

        $data = $request->validate([
            'attended_on' => ['required', 'date'],
            'status' => ['required', 'string', 'in:present,absent,late,excused'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $attendance->update($data + ['academic_year_id' => $this->currentYear()?->id, 'recorded_by' => auth()->id()]);

        return back()->with('success', 'Đã cập nhật điểm danh.');
    }

    public function comments(Request $request)
    {
        $teacher = $this->teacher();
        $year = $this->currentYear();
        $semester = $this->currentSemester($year);

        $assignments = $teacher->assignments()
            ->with(['class.grade', 'subject', 'semester'])
            ->where('academic_year_id', $year?->id)
            ->where('status', 'active')
            ->get();
        $homeroomClasses = $teacher->homeroomClasses()
            ->with('grade')
            ->where('academic_year_id', $year?->id)
            ->get();
        $classes = $assignments->pluck('class')
            ->merge($homeroomClasses)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $classId = (int) $request->input('class_id');
        $class = $classes->firstWhere('id', $classId) ?: $classes->first();
        $classId = $class?->id;

        $classAssignments = $assignments->where('class_id', $classId)->values();
        $subjects = $classAssignments->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
        $subjectId = $request->filled('subject_id') ? (int) $request->input('subject_id') : null;
        if ($subjectId && ! $subjects->contains('id', $subjectId)) {
            $subjectId = null;
        }

        $semesterId = $request->filled('semester_id') ? (int) $request->input('semester_id') : $semester?->id;
        $semesterOptions = $year?->semesters()->orderBy('number')->get() ?? collect();
        if ($semesterId && ! $semesterOptions->contains('id', $semesterId)) {
            $semesterId = $semester?->id;
        }
        $selectedSemester = $semesterOptions->firstWhere('id', $semesterId) ?: $semester;

        $students = $class
            ? $class->enrollments()
            ->with('student')
            ->where('academic_year_id', $year?->id)
            ->where('status', 'active')
            ->whereHas('student', fn($query) => $query->where('status', Student::STATUS_STUDYING))
            ->orderBy('roll_number')
            ->paginate(15)
            ->withQueryString()
            : collect();
        $commentMap = $students->isNotEmpty() && $year && $selectedSemester
            ? TeacherComment::with(['subject', 'teacher'])
            ->where('academic_year_id', $year->id)
            ->where('semester_id', $selectedSemester->id)
            ->whereIn('student_id', $students->pluck('student_id'))
            ->when($subjectId, fn($query) => $query->where('subject_id', $subjectId), fn($query) => $query->whereNull('subject_id'))
            ->latest()
            ->get()
            ->groupBy('student_id')
            ->map->first()
            : collect();

        return view('teacher.comments', compact(
            'teacher',
            'year',
            'semester',
            'semesterOptions',
            'selectedSemester',
            'classes',
            'class',
            'subjects',
            'subjectId',
            'students',
            'commentMap'
        ));
    }

    public function storeComment(Request $request)
    {
        $teacher = $this->teacher();
        if ($request->has('comments')) {
            return $this->storeCommentBatch($request, $teacher);
        }

        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'semester_id' => ['required', 'integer'],
            'content' => ['required', 'string', 'max:3000'],
        ]);

        $year = $this->currentYear();
        abort_unless($year, 422, 'No academic year is available.');
        $semester = Semester::whereKey($data['semester_id'])
            ->where('academic_year_id', $year->id)
            ->firstOrFail();
        $student = Student::findOrFail($data['student_id']);
        abort_unless($student->isStudying(), 422, 'Học sinh hiện không ở trạng thái "Đang học" nên không thể nhận nhận xét mới.');
        $classIds = $student->enrollments()
            ->where('academic_year_id', $year->id)
            ->where('status', 'active')
            ->pluck('class_id');

        $isHomeroomTeacher = $teacher->homeroomClasses()
            ->where('academic_year_id', $year->id)
            ->whereIn('id', $classIds)
            ->exists();
        $hasTeachingAssignment = $teacher->assignments()
            ->where('academic_year_id', $year->id)
            ->where('status', 'active')
            ->whereIn('class_id', $classIds)
            ->where(function ($query) use ($semester): void {
                $query->whereNull('semester_id')->orWhere('semester_id', $semester->id);
            })
            ->when($data['subject_id'], fn($query, $subjectId) => $query->where('subject_id', $subjectId))
            ->exists();

        abort_unless($isHomeroomTeacher || $hasTeachingAssignment, 403, 'Bạn không được phân công cho học sinh này.');

        $student->comments()->create([
            ...$data,
            'academic_year_id' => $year->id,
            'teacher_id' => $teacher->id,
        ]);

        return back()->with('success', 'Đã lưu nhận xét.');
    }

    private function storeCommentBatch(Request $request, $teacher)
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'semester_id' => ['required', 'integer'],
            'comments' => ['required', 'array', 'min:1'],
            'comments.*.content' => ['nullable', 'string', 'max:3000'],
        ]);

        $year = $this->currentYear();
        abort_unless($year, 422, 'Hệ thống chưa có năm học hiện tại.');
        $semester = Semester::whereKey($data['semester_id'])
            ->where('academic_year_id', $year->id)
            ->firstOrFail();
        $classIds = $teacher->homeroomClasses()
            ->where('academic_year_id', $year->id)
            ->pluck('id')
            ->merge($teacher->assignments()->where('academic_year_id', $year->id)->where('status', 'active')->pluck('class_id'))
            ->unique();
        abort_unless($classIds->contains((int) $data['class_id']), 403, 'Bạn không được phân công cho lớp này.');

        $studentIds = $classIds->contains((int) $data['class_id'])
            ? Student::where('status', Student::STATUS_STUDYING)
            ->whereHas('enrollments', fn($query) => $query->where('class_id', $data['class_id'])->where('academic_year_id', $year->id)->where('status', 'active'))->pluck('id')
            : collect();
        $saved = 0;
        foreach ($data['comments'] as $studentId => $row) {
            $studentId = (int) $studentId;
            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            abort_unless($studentIds->contains($studentId), 422, 'Học sinh không thuộc lớp được phân công.');

            $isHomeroomTeacher = $teacher->homeroomClasses()
                ->where('academic_year_id', $year->id)
                ->whereKey($data['class_id'])
                ->exists();
            $hasTeachingAssignment = $teacher->assignments()
                ->where('academic_year_id', $year->id)
                ->where('status', 'active')
                ->where('class_id', $data['class_id'])
                ->where(function ($query) use ($semester): void {
                    $query->whereNull('semester_id')->orWhere('semester_id', $semester->id);
                })
                ->when($data['subject_id'], fn($query, $subjectId) => $query->where('subject_id', $subjectId))
                ->exists();
            abort_unless($isHomeroomTeacher || $hasTeachingAssignment, 403, 'Bạn không được phân công cho học sinh này.');

            TeacherComment::create([
                'academic_year_id' => $year->id,
                'semester_id' => $semester->id,
                'student_id' => $studentId,
                'subject_id' => $data['subject_id'] ?: null,
                'teacher_id' => $teacher->id,
                'content' => $content,
            ]);
            $saved++;
        }

        return back()->with('success', "Đã lưu {$saved} nhận xét.");
    }

    public function updateComment(Request $request, TeacherComment $comment)
    {
        $teacher = $this->teacher();
        abort_unless((int) $comment->teacher_id === (int) $teacher->id, 403, 'Bạn không có quyền sửa nhận xét này.');
        $year = $this->currentYear();
        abort_unless($year && (int) $comment->academic_year_id === (int) $year->id, 403, 'Chỉ được sửa nhận xét trong năm học hiện tại.');

        $data = $request->validate([
            'content' => ['required', 'string', 'max:3000'],
        ]);
        $comment->update($data);

        return back()->with('success', 'Đã cập nhật nhận xét.');
    }

    private function teacher()
    {
        $teacher = auth()->user()->teacherProfile;
        abort_unless($teacher, 403, 'Tài khoản chưa được liên kết với hồ sơ giáo viên.');
        abort_if($teacher->status !== 'active', 403, 'Giáo viên tạm nghỉ hoặc ngừng công tác không thể sử dụng chức năng giảng dạy.');

        return $teacher;
    }

    private function weightedAverage($scores): ?float
    {
        $weights = [
            'oral' => 1,
            'fifteen_minute' => 1,
            'one_period' => 1,
            'midterm' => 2,
            'final' => 3,
        ];
        $total = 0;
        $weightTotal = 0;
        foreach ($scores as $score) {
            $weight = $weights[$score->score_type] ?? 1;
            $total += (float) $score->score * $weight;
            $weightTotal += $weight;
        }

        return $weightTotal > 0 ? round($total / $weightTotal, 2) : null;
    }

    private function academicSummaries($scores)
    {
        return $scores->groupBy('student_id')->map(function ($studentScores): array {
            $subjectAverages = $studentScores->groupBy('subject_id')
                ->map(fn($subjectScores) => $this->weightedAverage($subjectScores))
                ->filter(fn($average) => $average !== null)
                ->values();
            $average = $subjectAverages->isNotEmpty() ? round((float) $subjectAverages->avg(), 2) : null;
            $lowestSubject = $subjectAverages->isNotEmpty() ? (float) $subjectAverages->min() : null;

            return [
                'average' => $average,
                'rating' => $this->academicRating($average),
                'reward' => $average !== null && $average >= 8 && ($lowestSubject === null || $lowestSubject >= 6.5),
            ];
        });
    }

    private function academicRating(?float $average): string
    {
        if ($average === null) {
            return 'Chưa đủ dữ liệu';
        }
        if ($average >= 8) {
            return 'Giỏi';
        }
        if ($average >= 6.5) {
            return 'Khá';
        }
        if ($average >= 5) {
            return 'Trung bình';
        }

        return 'Yếu';
    }

    private function currentYear(): ?AcademicYear
    {
        return AcademicYear::where('is_current', true)->first() ?: AcademicYear::latest('starts_on')->first();
    }

    private function currentSemester(?AcademicYear $year): ?Semester
    {
        return $year?->semesters()->where('is_current', true)->first()
            ?: $year?->semesters()->orderBy('number')->first();
    }

    private function teacherAnnouncements($classIds)
    {
        return Announcement::query()
            ->where('is_published', true)
            ->where(function ($query) use ($classIds): void {
                $query->where('audience', 'all')
                    ->orWhere(function ($query) use ($classIds): void {
                        $query->where('audience', 'teachers')
                            ->where(function ($query) use ($classIds): void {
                                $query->whereNull('class_id')
                                    ->orWhereIn('class_id', $classIds);
                            });
                    });
            })
            ->where(function ($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest('published_at')
            ->limit(8)
            ->get();
    }

    private function teacherInvigilationNotifications()
    {
        return auth()->user()->notifications()
            ->where('type', ExamInvigilationAssigned::class)
            ->latest()
            ->limit(8)
            ->get();
    }

    private function teacherExams(?AcademicYear $year, ?Semester $semester, $classIds, $assignments = null)
    {
        $subjectIds = $assignments?->pluck('subject_id')->unique()->values() ?? collect();

        return Exam::with(['subject', 'class'])
            ->where('academic_year_id', $year?->id)
            ->when($semester, fn($query) => $query->where('semester_id', $semester->id))
            ->whereDate('exam_date', '>=', today())
            ->where(function ($query) use ($classIds, $subjectIds): void {
                $query->whereNull('class_id')->orWhereIn('class_id', $classIds);
                if ($subjectIds->isNotEmpty()) {
                    $query->orWhereIn('subject_id', $subjectIds);
                }
            })
            ->orderBy('exam_date')
            ->limit(8)
            ->get();
    }
}
