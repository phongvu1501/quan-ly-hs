<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\ConductEvaluation;
use App\Models\Exam;
use App\Models\ExamInvigilation;
use App\Models\ExamResult;
use App\Models\FeeNotice;
use App\Models\FeePayment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Notifications\ExamInvigilationAssigned;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolController extends Controller
{


    public function dashboard(Request $request)
    {

        $academicYears = DB::table('academic_years')
            ->select('id', 'name')
            ->orderByDesc('id')
            ->get();

        $currentYear = $this->currentYear();

        $filterAcademicYearId = $request->filled('academic_year_id')
            ? (int) $request->input('academic_year_id')
            : ($currentYear?->id);

        $filterGradeId = $request->filled('grade_id')
            ? (int) $request->input('grade_id')
            : null;

        $filterClassId = $request->filled('class_id')
            ? (int) $request->input('class_id')
            : null;


        $filterDateFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : today()->subDays(29)->startOfDay();

        $filterDateTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : today()->endOfDay();

        if ($filterDateFrom->gt($filterDateTo)) {
            [$filterDateFrom, $filterDateTo] = [
                $filterDateTo->copy()->startOfDay(),
                $filterDateFrom->copy()->endOfDay(),
            ];
        }

        $grades = DB::table('grades')
            ->select('id', 'name', 'level')
            ->orderBy('level')
            ->get();

        $classesFilter = DB::table('classes')
            ->select('id', 'name', 'grade_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $year = $filterAcademicYearId
            ? $academicYears->firstWhere('id', $filterAcademicYearId)
            : $currentYear;
        $studentQuery = DB::table('enrollments')
            ->join('classes', 'classes.id', '=', 'enrollments.class_id')
            ->where('enrollments.academic_year_id', $filterAcademicYearId)
            ->where('enrollments.status', 'active');

        if ($filterGradeId) {
            $studentQuery->where('classes.grade_id', $filterGradeId);
        }

        if ($filterClassId) {
            $studentQuery->where('classes.id', $filterClassId);
        }

        $activeStudents = (clone $studentQuery)
            ->distinct('enrollments.student_id')
            ->count('enrollments.student_id');

        $activeClasses = (clone $studentQuery)
            ->distinct('classes.id')
            ->count('classes.id');


        $gradeBreakdownQuery = DB::table('enrollments')
            ->join('classes', 'classes.id', '=', 'enrollments.class_id')
            ->join('grades', 'grades.id', '=', 'classes.grade_id')
            ->where('enrollments.academic_year_id', $filterAcademicYearId)
            ->where('enrollments.status', 'active');

        if ($filterGradeId) {
            $gradeBreakdownQuery->where('grades.id', $filterGradeId);
        }

        if ($filterClassId) {
            $gradeBreakdownQuery->where('classes.id', $filterClassId);
        }

        $gradeBreakdown = $gradeBreakdownQuery
            ->select(
                'grades.id',
                'grades.name',
                'grades.level',
                DB::raw('count(distinct enrollments.student_id) as total')
            )
            ->groupBy('grades.id', 'grades.name', 'grades.level')
            ->orderBy('grades.level')
            ->get();

        $attendance = $filterAcademicYearId
            ? AttendanceRecord::where('academic_year_id', $filterAcademicYearId)
            ->whereBetween('attended_on', [
                $filterDateFrom->toDateString(),
                $filterDateTo->toDateString(),
            ])
            ->select(
                'status',
                DB::raw('count(*) as total')
            )
            ->groupBy('status')
            ->pluck('total', 'status')
            : collect();


        $teachers = Teacher::where('status', 'active')->count();

        $subjects = Subject::where('is_active', true)->count();

        $scoreRatings = $filterAcademicYearId
            ? SubjectResult::where('academic_year_id', $filterAcademicYearId)
            ->select(
                'rating',
                DB::raw('count(*) as total')
            )
            ->groupBy('rating')
            ->pluck('total', 'rating')
            : collect();

        $scoreCount = $filterAcademicYearId
            ? Score::where('academic_year_id', $filterAcademicYearId)->count()
            : 0;

        $paidFees = 0;
        $outstandingFees = 0;

        if ($filterAcademicYearId) {
            $feeBase = DB::table('fee_payments')
                ->join(
                    'fee_notices',
                    'fee_notices.id',
                    '=',
                    'fee_payments.fee_notice_id'
                )
                ->where(
                    'fee_notices.academic_year_id',
                    $filterAcademicYearId
                );

            $paidFees = (clone $feeBase)
                ->where('fee_payments.status', 'paid')
                ->sum('fee_payments.amount');

            $outstandingFees = (clone $feeBase)
                ->whereIn(
                    'fee_payments.status',
                    ['unpaid', 'partial']
                )
                ->sum('fee_payments.amount');
        }

        $upcomingExamsQuery = Exam::with([
            'subject',
            'class',
        ])
            ->when(
                $filterAcademicYearId,
                fn($query) => $query->where(
                    'academic_year_id',
                    $filterAcademicYearId
                )
            )
            ->whereBetween(
                'exam_date',
                [
                    $filterDateFrom->toDateString(),
                    $filterDateTo->toDateString(),
                ]
            )
            ->orderBy('exam_date')
            ->limit(10);


        if ($filterClassId) {
            $upcomingExamsQuery->where('class_id', $filterClassId);
        } elseif ($filterGradeId) {
            $gradeClassIds = DB::table('classes')
                ->where('grade_id', $filterGradeId)
                ->pluck('id');

            $upcomingExamsQuery->whereIn('class_id', $gradeClassIds);
        }

        $upcomingExams = $upcomingExamsQuery->get();

        $examCountQuery = Exam::query()
            ->when(
                $filterAcademicYearId,
                fn($query) => $query->where(
                    'academic_year_id',
                    $filterAcademicYearId
                )
            )
            ->whereBetween(
                'exam_date',
                [
                    $filterDateFrom->toDateString(),
                    $filterDateTo->toDateString(),
                ]
            );

        if ($filterClassId) {
            $examCountQuery->where('class_id', $filterClassId);
        } elseif ($filterGradeId) {
            $gradeClassIds = DB::table('classes')
                ->where('grade_id', $filterGradeId)
                ->pluck('id');

            $examCountQuery->whereIn('class_id', $gradeClassIds);
        }

        $examCount = $examCountQuery->count();

        $recentAnnouncements = Announcement::where(
            'is_published',
            true
        )
            ->latest('published_at')
            ->limit(5)
            ->get();

        return view('admin.school.dashboard', [
            'year' => $year,

            'academicYears' => $academicYears,
            'grades' => $grades,
            'classesFilter' => $classesFilter,

            'filterAcademicYearId' => $filterAcademicYearId,
            'filterGradeId' => $filterGradeId,
            'filterClassId' => $filterClassId,

            'filterDateFrom' => $filterDateFrom->format('Y-m-d'),
            'filterDateTo' => $filterDateTo->format('Y-m-d'),

            'students' => $activeStudents,
            'teachers' => $teachers,
            'classes' => $activeClasses,
            'subjects' => $subjects,

            'recentAnnouncements' => $recentAnnouncements,
            'upcomingExams' => $upcomingExams,

            'attendance' => $attendance,
            'gradeBreakdown' => $gradeBreakdown,
            'scoreRatings' => $scoreRatings,

            'paidFees' => $paidFees,
            'outstandingFees' => $outstandingFees,

            'scoreCount' => $scoreCount,
            'examCount' => $examCount,
        ]);
    }



    public function students(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $status = trim((string) $request->input('status', ''));
        $students = Student::with(['enrollments' => fn($query) => $query
            ->with('class.grade', 'academicYear')
            ->where('status', 'active')
            ->latest('id')])
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('student_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->when($yearId || $classId, fn($query) => $query->whereHas('enrollments', function ($enrollment) use ($yearId, $classId): void {
                $enrollment->when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
                    ->when($classId, fn($q) => $q->where('class_id', $classId));
            }))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.students.index', [
            'students' => $students,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with(['academicYear', 'grade'])->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'studentStatuses' => ['studying' => 'Đang đi học', 'sick_leave' => 'Nghỉ ốm dài ngày', 'reserved' => 'Bảo lưu', 'dropped' => 'Bỏ học', 'graduated' => 'Đã tốt nghiệp'],
        ]);
    }

    public function createStudent()
    {
        return view('admin.school.students.form', [
            'student' => null,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    public function storeStudent(Request $request)
    {
        $validated = $this->studentData($request);
        $student = DB::transaction(function () use ($validated): Student {
            $student = Student::create($validated['student']);
            if ($validated['avatar']) {
                $student->update(['avatar_path' => $validated['avatar']->store('avatars/students', 'public')]);
            }
            $this->syncEnrollment($student, $validated['academic_year_id'], $validated['class_id']);
            $this->createStudentAccount($student);

            return $student;
        });

        return to_route('admin.school.students.index')->with('success', "Đã tạo học sinh và tài khoản {$student->email}. Mật khẩu mặc định: 123456. Học sinh phải đổi mật khẩu khi đăng nhập lần đầu.");
    }

    public function editStudent(Student $student)
    {
        $student->load(['enrollments' => fn($query) => $query->where('status', 'active')->latest('academic_year_id')]);

        return view('admin.school.students.form', [
            'student' => $student,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    public function updateStudent(Request $request, Student $student)
    {
        $validated = $this->studentData($request, $student);
        DB::transaction(function () use ($student, $validated): void {
            $student->update($validated['student']);
            if ($validated['avatar']) {
                if ($student->avatar_path) {
                    Storage::disk('public')->delete($student->avatar_path);
                }
                $student->update(['avatar_path' => $validated['avatar']->store('avatars/students', 'public')]);
            }
            $this->syncEnrollment($student, $validated['academic_year_id'], $validated['class_id']);
        });

        return to_route('admin.school.students.index')->with('success', 'Đã cập nhật hồ sơ học sinh.');
    }

    public function destroyStudent(Student $student)
    {
        if ($student->enrollments()->exists()) {
            return back()->with('error', 'Học sinh đã có lịch sử học tập nên không thể xóa. Hãy chuyển trạng thái thay vì xóa.');
        }

        if ($student->avatar_path) {
            Storage::disk('public')->delete($student->avatar_path);
        }
        $student->delete();

        return back()->with('success', 'Đã xóa học sinh.');
    }

    public function studentPayments(Student $student)
    {
        $student->load(['enrollments.class.grade', 'payments.feeNotice.academicYear', 'payments.feeNotice.semester']);
        $year = $this->currentYear();
        $classId = $student->enrollments()->where('academic_year_id', $year?->id)->value('class_id');
        $notices = FeeNotice::where('academic_year_id', $year?->id)
            ->where('is_active', true)
            ->where(fn($query) => $query->whereNull('class_id')->orWhere('class_id', $classId))
            ->with(['payments' => fn($query) => $query->where('student_id', $student->id)])
            ->latest()
            ->get();

        return view('admin.school.students.payments', compact('student', 'year', 'notices'));
    }

    public function storeStudentPayment(Request $request, Student $student)
    {
        $data = $request->validate([
            'fee_notice_id' => ['required', 'exists:fee_notices,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['unpaid', 'pending', 'paid', 'refunded'])],
            'paid_at' => ['nullable', 'date'],
            'transaction_code' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string'],
        ]);
        if ($data['status'] === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }
        FeePayment::updateOrCreate(
            ['fee_notice_id' => $data['fee_notice_id'], 'student_id' => $student->id],
            $data + ['student_id' => $student->id]
        );

        return back()->with('success', 'Đã cập nhật lịch sử đóng tiền.');
    }

    public function studentTranscript(Student $student)
    {
        $enrollments = $student->enrollments()
            ->with(['academicYear', 'class.grade'])
            ->orderByDesc('academic_year_id')
            ->get();
        $scores = $student->scores()
            ->with(['semester', 'class', 'subject', 'teacher'])
            ->orderByDesc('academic_year_id')
            ->orderByDesc('semester_id')
            ->latest('assessed_on')
            ->get();
        $subjectResults = SubjectResult::with(['academicYear', 'semester', 'subject'])
            ->where('student_id', $student->id)
            ->latest('academic_year_id')
            ->latest('semester_id')
            ->get();
        $conductEvaluations = ConductEvaluation::with(['academicYear', 'semester'])
            ->where('student_id', $student->id)
            ->latest('academic_year_id')
            ->latest('semester_id')
            ->get();
        $examResults = ExamResult::with(['exam.academicYear', 'exam.semester', 'exam.subject'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();
        $attendanceSummary = $student->attendance()
            ->selectRaw('academic_year_id, status, COUNT(*) as total')
            ->groupBy('academic_year_id', 'status')
            ->get()
            ->groupBy('academic_year_id');

        $yearIds = collect()
            ->merge($enrollments->pluck('academic_year_id'))
            ->merge($scores->pluck('academic_year_id'))
            ->merge($subjectResults->pluck('academic_year_id'))
            ->merge($conductEvaluations->pluck('academic_year_id'))
            ->merge($examResults->pluck('exam.academic_year_id'))
            ->filter()
            ->unique()
            ->values();
        $years = AcademicYear::whereIn('id', $yearIds)->orderByDesc('starts_on')->get();

        return view('admin.school.students.transcript', compact(
            'student',
            'years',
            'enrollments',
            'scores',
            'subjectResults',
            'conductEvaluations',
            'examResults',
            'attendanceSummary'
        ));
    }

    public function teachers(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $status = trim((string) $request->input('status', ''));
        $type = trim((string) $request->input('type', ''));
        $teachers = Teacher::withCount(['homeroomClasses', 'assignments'])
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('teacher_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->when($type === 'homeroom', fn($query) => $query->whereHas('homeroomClasses'))
            ->when($type === 'subject', fn($query) => $query->whereHas('assignments'))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.teachers.index', [
            'teachers' => $teachers,
            'teacherStatuses' => ['active' => 'Đang công tác', 'on_leave' => 'Tạm nghỉ', 'inactive' => 'Ngừng công tác'],
            'teacherType' => $type,
        ]);
    }

    public function createTeacher()
    {
        return view('admin.school.teachers.form', [
            'teacher' => null,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    public function homeroomTeachers(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $teachers = Teacher::with(['homeroomClasses' => fn($query) => $query
            ->with(['academicYear', 'grade'])
            ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId))])
            ->whereHas('homeroomClasses', fn($query) => $query
                ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId)))
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('teacher_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhereHas('homeroomClasses', fn($class) => $class
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            }))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.teachers.homeroom', [
            'teachers' => $teachers,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
        ]);
    }

    public function subjectTeachers(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $teachers = Teacher::with(['assignments' => fn($query) => $query
            ->with(['academicYear', 'semester', 'class.grade', 'subject'])
            ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId))])
            ->whereHas('assignments', fn($query) => $query
                ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId)))
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('teacher_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhereHas('assignments.subject', fn($subject) => $subject
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            }))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.teachers.subject', [
            'teachers' => $teachers,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
        ]);
    }

    public function storeTeacher(Request $request)
    {
        $teacherData = $this->teacherData($request);
        $teacher = DB::transaction(function () use ($request, $teacherData): Teacher {
            $teacher = Teacher::create($teacherData);
            $this->storeTeacherAvatar($request, $teacher);
            $this->syncTeacherProfile($request, $teacher);
            $this->createTeacherAccount($request, $teacher);

            return $teacher;
        });

        return to_route('admin.school.teachers.index')->with('success', "Đã tạo giáo viên và tài khoản {$teacher->email}. Mật khẩu mặc định: 123456. Giáo viên phải đổi mật khẩu khi đăng nhập lần đầu.");
    }

    public function editTeacher(Teacher $teacher)
    {
        $teacher->load(['subjects', 'homeroomClasses']);

        return view('admin.school.teachers.form', [
            'teacher' => $teacher,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    public function teacherHistory(Teacher $teacher)
    {
        $assignments = $teacher->assignments()
            ->with(['academicYear', 'semester', 'class.grade', 'subject'])
            ->orderByDesc('academic_year_id')
            ->orderBy('class_id')
            ->get();
        $homeroomClasses = $teacher->homeroomClasses()
            ->with(['academicYear', 'grade'])
            ->orderByDesc('academic_year_id')
            ->get();
        $schedules = $teacher->schedules()
            ->with(['academicYear', 'semester', 'class.grade', 'subject'])
            ->orderByDesc('academic_year_id')
            ->orderBy('weekday')
            ->orderBy('period')
            ->get();
        $examInvigilations = $teacher->examInvigilations()
            ->with(['exam.academicYear', 'exam.semester', 'exam.subject', 'exam.class'])
            ->whereHas('exam')
            ->get()
            ->sortByDesc(fn($assignment) => $assignment->exam?->exam_date?->format('Y-m-d') . ' ' . ($assignment->exam?->starts_at ?? ''))
            ->values();
        $subjectYears = $teacher->subjects->groupBy(fn($subject) => $subject->pivot->academic_year_id);
        $yearIds = collect()
            ->merge($assignments->pluck('academic_year_id'))
            ->merge($homeroomClasses->pluck('academic_year_id'))
            ->merge($schedules->pluck('academic_year_id'))
            ->merge($examInvigilations->pluck('exam.academic_year_id'))
            ->merge($subjectYears->keys())
            ->filter()
            ->unique()
            ->values();
        $years = AcademicYear::whereIn('id', $yearIds)->orderByDesc('starts_on')->get();

        return view('admin.school.teachers.history', compact(
            'teacher',
            'years',
            'assignments',
            'homeroomClasses',
            'schedules',
            'subjectYears',
            'examInvigilations'
        ));
    }

    public function updateTeacher(Request $request, Teacher $teacher)
    {
        DB::transaction(function () use ($request, $teacher): void {
            $teacher->update($this->teacherData($request, $teacher));
            $this->storeTeacherAvatar($request, $teacher);
            $this->syncTeacherProfile($request, $teacher);
        });

        return to_route('admin.school.teachers.index')->with('success', 'Đã cập nhật hồ sơ giáo viên.');
    }

    public function destroyTeacher(Teacher $teacher)
    {
        if ($teacher->homeroomClasses()->exists() || $teacher->assignments()->exists() || $teacher->examInvigilations()->exists()) {
            return back()->with('error', 'Giáo viên đang có lớp, môn hoặc lịch coi thi nên không thể xóa.');
        }

        if ($teacher->avatar_path) {
            Storage::disk('public')->delete($teacher->avatar_path);
        }
        $teacher->delete();

        return back()->with('success', 'Đã xóa giáo viên.');
    }

    public function grades(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $level = $request->filled('level') ? (int) $request->input('level') : null;
        $grades = Grade::withCount('classes')
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('level', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($level, fn($query) => $query->where('level', $level))
            ->orderBy('level')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.grades.index', [
            'grades' => $grades,
            'levels' => Grade::orderBy('level')->pluck('level'),
        ]);
    }

    public function createGrade()
    {
        return view('admin.school.grades.form', ['grade' => null]);
    }

    public function storeGrade(Request $request)
    {
        Grade::create($request->validate([
            'level' => ['required', 'integer', 'between:10,12', 'unique:grades,level'],
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]));

        return to_route('admin.school.grades.index')->with('success', 'Đã tạo khối lớp.');
    }

    public function editGrade(Grade $grade)
    {
        return view('admin.school.grades.form', compact('grade'));
    }

    public function updateGrade(Request $request, Grade $grade)
    {
        $grade->update($request->validate([
            'level' => ['required', 'integer', 'between:10,12', Rule::unique('grades', 'level')->ignore($grade)],
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]));

        return to_route('admin.school.grades.index')->with('success', 'Đã cập nhật khối lớp.');
    }

    public function destroyGrade(Grade $grade)
    {
        if ($grade->classes()->exists()) {
            return back()->with('error', 'Khối lớp đang có lớp sử dụng nên không thể xóa.');
        }
        $grade->delete();

        return back()->with('success', 'Đã xóa khối lớp.');
    }

    public function subjects(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $active = $request->input('active', '');
        $subjects = Subject::withCount(['assignments'])
            ->with(['assignments.teacher'])
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            }))
            ->when($active !== '', fn($query) => $query->where('is_active', $active === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.subjects.index', [
            'subjects' => $subjects,
            'subjectStatuses' => ['active' => 'Đang dùng', 'inactive' => 'Tạm ẩn'],
        ]);
    }

    public function createSubject()
    {
        return view('admin.school.subjects.form', ['subject' => null]);
    }

    public function storeSubject(Request $request)
    {
        Subject::create($request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'weekly_periods' => ['required', 'integer', 'between:1,20'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return to_route('admin.school.subjects.index')->with('success', 'Đã tạo môn học.');
    }

    public function editSubject(Subject $subject)
    {
        return view('admin.school.subjects.form', compact('subject'));
    }

    public function updateSubject(Request $request, Subject $subject)
    {
        $subject->update($request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('subjects', 'code')->ignore($subject)],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'weekly_periods' => ['required', 'integer', 'between:1,20'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return to_route('admin.school.subjects.index')->with('success', 'Đã cập nhật môn học.');
    }

    public function destroySubject(Subject $subject)
    {
        if ($subject->assignments()->exists()) {
            return back()->with('error', 'Môn học đang có phân công nên không thể xóa.');
        }
        $subject->delete();

        return back()->with('success', 'Đã xóa môn học.');
    }

    public function classes(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $gradeId = $request->filled('grade_id') ? (int) $request->input('grade_id') : null;
        $status = trim((string) $request->input('status', ''));
        $classes = SchoolClass::with(['academicYear', 'grade', 'homeroomTeacher'])
            ->withCount(['enrollments' => fn($query) => $query->where('status', 'active')])
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('grade', fn($grade) => $grade->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('homeroomTeacher', fn($teacher) => $teacher->where('full_name', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId))
            ->when($gradeId, fn($query) => $query->where('grade_id', $gradeId))
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->orderByDesc('academic_year_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.classes.index', [
            'classes' => $classes,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'classStatuses' => ['active' => 'Đang hoạt động', 'inactive' => 'Tạm dừng'],
        ]);
    }

    public function createClass()
    {
        return view('admin.school.classes.form', [
            'schoolClass' => null,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'teachers' => Teacher::with('subjects')->where('status', 'active')->orderBy('full_name')->get(),
        ]);
    }

    public function classStudents(Request $request, SchoolClass $schoolClass)
    {
        $search = trim((string) $request->input('q', ''));
        $schoolClass->load(['academicYear', 'grade', 'homeroomTeacher']);
        $enrollments = $schoolClass->enrollments()
            ->with('student')
            ->where('status', 'active')
            ->when($search !== '', fn($query) => $query->whereHas('student', function ($student) use ($search): void {
                $student->where('student_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderByRaw('roll_number IS NULL')
            ->orderBy('roll_number')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.school.classes.students', [
            'schoolClass' => $schoolClass,
            'enrollments' => $enrollments,
        ]);
    }

    public function storeClass(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => ['nullable', 'exists:academic_years,id', 'required_without:academic_year_name'],
            'academic_year_name' => ['nullable', 'string', 'max:30', 'required_without:academic_year_id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'homeroom_teacher_id' => ['nullable', 'exists:teachers,id'],
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'room' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'between:1,100'],
            'notes' => ['nullable', 'string'],
        ]);
        $year = $this->resolveAcademicYear($data['academic_year_id'] ?? null, $data['academic_year_name'] ?? null);
        if (SchoolClass::where('academic_year_id', $year->id)->where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Mã lớp đã tồn tại trong năm học này.']);
        }
        $this->validateHomeroomTeacher($data['homeroom_teacher_id'] ?? null, $year->id);
        $data['academic_year_id'] = $year->id;
        unset($data['academic_year_name']);
        SchoolClass::create($data);

        return to_route('admin.school.classes.index')->with('success', 'Đã tạo lớp học.');
    }

    public function editClass(SchoolClass $schoolClass)
    {
        return view('admin.school.classes.form', [
            'schoolClass' => $schoolClass,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'teachers' => Teacher::where(function ($query) use ($schoolClass): void {
                $query->where('status', 'active')
                    ->when($schoolClass->homeroom_teacher_id, fn($query) => $query->orWhere('id', $schoolClass->homeroom_teacher_id));
            })->orderBy('full_name')->get(),
        ]);
    }

    public function updateClass(Request $request, SchoolClass $schoolClass)
    {
        $data = $request->validate([
            'academic_year_id' => ['nullable', 'exists:academic_years,id', 'required_without:academic_year_name'],
            'academic_year_name' => ['nullable', 'string', 'max:30', 'required_without:academic_year_id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'homeroom_teacher_id' => ['nullable', 'exists:teachers,id'],
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'room' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'between:1,100'],
            'notes' => ['nullable', 'string'],
        ]);
        $year = $this->resolveAcademicYear($data['academic_year_id'] ?? null, $data['academic_year_name'] ?? null);
        if (SchoolClass::where('academic_year_id', $year->id)->where('code', $data['code'])->whereKeyNot($schoolClass->id)->exists()) {
            throw ValidationException::withMessages(['code' => 'Mã lớp đã tồn tại trong năm học này.']);
        }
        $this->validateHomeroomTeacher(
            $data['homeroom_teacher_id'] ?? null,
            $year->id,
            $schoolClass->id,
            $schoolClass->homeroom_teacher_id
        );
        $data['academic_year_id'] = $year->id;
        unset($data['academic_year_name']);
        $schoolClass->update($data);

        return to_route('admin.school.classes.index')->with('success', 'Đã cập nhật lớp học.');
    }

    public function destroyClass(SchoolClass $schoolClass)
    {
        if ($schoolClass->enrollments()->exists()) {
            return back()->with('error', 'Lớp đang có học sinh nên không thể xóa.');
        }
        $schoolClass->delete();

        return back()->with('success', 'Đã xóa lớp học.');
    }

    public function assignments(Request $request)
    {
        $currentYear = $this->currentYear();
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $semesterId = $request->filled('semester_id') ? (int) $request->input('semester_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $subjectId = $request->filled('subject_id') ? (int) $request->input('subject_id') : null;
        $teacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;
        $assignments = TeachingAssignment::with(['academicYear', 'semester', 'class', 'subject', 'teacher'])
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('class', fn($class) => $class->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn($subject) => $subject->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('teacher', fn($teacher) => $teacher->where('full_name', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId))
            ->when($semesterId, fn($query) => $query->where('semester_id', $semesterId))
            ->when($classId, fn($query) => $query->where('class_id', $classId))
            ->when($subjectId, fn($query) => $query->where('subject_id', $subjectId))
            ->when($teacherId, fn($query) => $query->where('teacher_id', $teacherId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.school.assignments.index', [
            'assignments' => $assignments,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            // Keep every teacher currently working at the school in the selector.
            // Qualification for a subject is still enforced by storeAssignment().
            'teachers' => Teacher::with('subjects')->where('status', 'active')->orderBy('full_name')->get(),
            'currentYearId' => $currentYear?->id,
        ]);
    }

    public function editAssignment(TeachingAssignment $assignment)
    {
        return view('admin.school.assignments.form', [
            'assignment' => $assignment->load(['academicYear', 'semester', 'class', 'subject', 'teacher']),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'teachers' => Teacher::where('status', 'active')->orderBy('full_name')->get(),
        ]);
    }

    public function storeAssignment(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
        ]);

        if (! SchoolClass::whereKey($data['class_id'])->where('academic_year_id', $data['academic_year_id'])->exists()) {
            throw ValidationException::withMessages(['class_id' => 'Lớp không thuộc năm học đã chọn.']);
        }

        $teacher = Teacher::findOrFail($data['teacher_id']);
        if ($teacher->status !== 'active') {
            throw ValidationException::withMessages(['teacher_id' => 'Chỉ giáo viên đang công tác mới được phân công giảng dạy.']);
        }

        if (! empty($data['semester_id']) && ! Semester::whereKey($data['semester_id'])->where('academic_year_id', $data['academic_year_id'])->exists()) {
            throw ValidationException::withMessages(['semester_id' => 'Học kỳ không thuộc năm học đã chọn.']);
        }

        if (TeachingAssignment::where($data)->exists()) {
            throw ValidationException::withMessages(['teacher_id' => 'Phân công này đã tồn tại.']);
        }

        DB::table('teacher_subject')->upsert([[
            'teacher_id' => $data['teacher_id'],
            'subject_id' => $data['subject_id'],
            'academic_year_id' => $data['academic_year_id'],
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['teacher_id', 'subject_id', 'academic_year_id'], ['updated_at']);

        TeachingAssignment::create($data);

        return back()->with('success', 'Đã tạo phân công giảng dạy.');
    }

    public function updateAssignment(Request $request, TeachingAssignment $assignment)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
        ]);

        if (! SchoolClass::whereKey($data['class_id'])->where('academic_year_id', $data['academic_year_id'])->exists()) {
            throw ValidationException::withMessages(['class_id' => 'Lớp không thuộc năm học đã chọn.']);
        }
        if (! empty($data['semester_id']) && ! Semester::whereKey($data['semester_id'])->where('academic_year_id', $data['academic_year_id'])->exists()) {
            throw ValidationException::withMessages(['semester_id' => 'Học kỳ không thuộc năm học đã chọn.']);
        }
        $teacher = Teacher::findOrFail($data['teacher_id']);
        if ($teacher->status !== 'active') {
            throw ValidationException::withMessages(['teacher_id' => 'Chỉ giáo viên đang công tác mới được phân công giảng dạy.']);
        }
        $duplicate = TeachingAssignment::where($data)->whereKeyNot($assignment->id)->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['teacher_id' => 'Phân công này đã tồn tại.']);
        }
        $assignment->update($data);

        return to_route('admin.school.assignments.index')->with('success', 'Đã cập nhật phân công giảng dạy.');
    }

    public function subjectTeacherDetail(Subject $subject, Teacher $teacher)
    {
        $assignments = $subject->assignments()
            ->where('teacher_id', $teacher->id)
            ->with(['academicYear', 'semester', 'class.grade'])
            ->orderByDesc('academic_year_id')
            ->get();

        abort_unless($assignments->isNotEmpty(), 404);

        $yearIds = $assignments->pluck('academic_year_id')->unique()->values();
        $homeroomClasses = $teacher->homeroomClasses()
            ->with(['academicYear', 'grade'])
            ->whereIn('academic_year_id', $yearIds)
            ->orderByDesc('academic_year_id')
            ->orderBy('name')
            ->get();
        $classes = SchoolClass::with(['academicYear', 'grade'])
            ->whereIn('academic_year_id', $yearIds)
            ->where('status', 'active')
            ->orderByDesc('academic_year_id')
            ->orderBy('name')
            ->get();

        return view('admin.school.subjects.teacher-detail', compact(
            'subject',
            'teacher',
            'assignments',
            'homeroomClasses',
            'classes'
        ));
    }

    public function destroyAssignment(TeachingAssignment $assignment)
    {
        $assignment->delete();

        return back()->with('success', 'Đã xóa phân công giảng dạy.');
    }

    // public function scores(Request $request)
    // {
    //     $search = trim((string) $request->input('q', ''));
    //     $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
    //     $semesterId = $request->filled('semester_id') ? (int) $request->input('semester_id') : null;
    //     $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
    //     $gradeId = $request->filled('grade_id') ? (int) $request->input('grade_id') : null;
    //     $subjectId = $request->filled('subject_id') ? (int) $request->input('subject_id') : null;
    //     $scoreType = trim((string) $request->input('score_type', ''));
    //     $dateFrom = $request->date('date_from');
    //     $dateTo = $request->date('date_to');
    //     $scoreRows = Score::with(['student', 'class', 'subject', 'semester', 'academicYear'])
    //         ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
    //             $query->whereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$search}%")->orWhere('student_code', 'like', "%{$search}%"))
    //                 ->orWhereHas('class', fn ($class) => $class->where('name', 'like', "%{$search}%"))
    //                 ->orWhereHas('subject', fn ($subject) => $subject->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
    //         }))
    //         ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
    //         ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
    //         ->when($classId, fn ($query) => $query->where('class_id', $classId))
    //         ->when($gradeId, fn ($query) => $query->whereHas('class', fn ($class) => $class->where('grade_id', $gradeId)))
    //         ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
    //         ->when($scoreType !== '', fn ($query) => $query->where('score_type', $scoreType))
    //         ->when($dateFrom, fn ($query) => $query->whereDate('assessed_on', '>=', $dateFrom))
    //         ->when($dateTo, fn ($query) => $query->whereDate('assessed_on', '<=', $dateTo))
    //         ->latest('assessed_on')->latest()->get();

    //     $scoreGroups = $scoreRows->groupBy(fn ($score) => implode('|', [
    //         $score->academic_year_id,
    //         $score->semester_id,
    //         $score->student_id,
    //         $score->class_id,
    //         $score->subject_id,
    //     ]))->map(function ($group): object {
    //         $first = $group->first();

    //         return (object) [
    //             'scores' => $group->sortByDesc(fn ($score) => $score->assessed_on?->timestamp ?: 0)->values(),
    //             'student' => $first->student,
    //             'class' => $first->class,
    //             'subject' => $first->subject,
    //             'semester' => $first->semester,
    //             'academicYear' => $first->academicYear,
    //             'latestTimestamp' => $group->max(fn ($score) => $score->assessed_on?->timestamp ?: 0),
    //         ];
    //     })->sortByDesc('latestTimestamp')->values();
    //     $page = max(1, (int) $request->input('page', 1));
    //     $scores = new LengthAwarePaginator(
    //         $scoreGroups->forPage($page, 20)->values(),
    //         $scoreGroups->count(),
    //         20,
    //         $page,
    //         ['path' => $request->url(), 'query' => $request->query()]
    //     );

    //     return view('admin.school.scores.index', [
    //         'scores' => $scores,
    //         'years' => AcademicYear::orderByDesc('starts_on')->get(),
    //         'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
    //         'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
    //         'grades' => Grade::orderBy('level')->get(),
    //         'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
    //         'scoreTypes' => ['oral' => 'Miệng', 'fifteen_minute' => '15 phút', 'one_period' => '1 tiết', 'midterm' => 'Giữa kỳ', 'final' => 'Cuối kỳ'],
    //     ]);
    // }

    public function scores(Request $request)
    {
        $filters = [
            'search'      => trim($request->input('q', '')),
            'year_id'     => $request->integer('academic_year_id'),
            'semester_id' => $request->integer('semester_id'),
            'class_id'    => $request->integer('class_id'),
            'grade_id'    => $request->integer('grade_id'),
            'subject_id'  => $request->integer('subject_id'),
            'score_type'  => trim($request->input('score_type', '')),
            'date_from'   => $request->date('date_from'),
            'date_to'     => $request->date('date_to'),
        ];

        $scoreRows = Score::with([
            'student',
            'class',
            'subject',
            'semester',
            'academicYear',
        ])
            ->when($filters['search'], function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas(
                        'student',
                        fn($q) =>
                        $q->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_code', 'like', "%{$search}%")
                    )
                        ->orWhereHas(
                            'class',
                            fn($q) =>
                            $q->where('name', 'like', "%{$search}%")
                        )
                        ->orWhereHas(
                            'subject',
                            fn($q) =>
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                        );
                });
            })
            ->when($filters['year_id'], fn($q, $id) => $q->where('academic_year_id', $id))
            ->when($filters['semester_id'], fn($q, $id) => $q->where('semester_id', $id))
            ->when($filters['class_id'], fn($q, $id) => $q->where('class_id', $id))
            ->when(
                $filters['grade_id'],
                fn($q, $id) =>
                $q->whereHas('class', fn($q) => $q->where('grade_id', $filters['grade_id']))
            )
            ->when($filters['subject_id'], fn($q, $id) => $q->where('subject_id', $id))
            ->when($filters['score_type'], fn($q, $type) => $q->where('score_type', $type))
            ->when($filters['date_from'], fn($q, $date) => $q->whereDate('assessed_on', '>=', $date))
            ->when($filters['date_to'], fn($q, $date) => $q->whereDate('assessed_on', '<=', $date))
            ->latest('assessed_on')
            ->get();

        $scoreGroups = $scoreRows
            ->groupBy(fn($score) => "{$score->academic_year_id}|{$score->semester_id}|{$score->student_id}|{$score->class_id}|{$score->subject_id}")
            ->map(function ($group) {
                $first = $group->first();

                return (object) [
                    'scores'          => $group->sortByDesc('assessed_on')->values(),
                    'student'         => $first->student,
                    'class'           => $first->class,
                    'subject'         => $first->subject,
                    'semester'        => $first->semester,
                    'academicYear'    => $first->academicYear,
                    'latestTimestamp' => $group->max(fn($score) => $score->assessed_on?->timestamp ?? 0),
                ];
            })
            ->sortByDesc('latestTimestamp')
            ->values();

        $perPage = 20;
        $page = $request->integer('page', 1);

        $scores = new LengthAwarePaginator(
            $scoreGroups->forPage($page, $perPage)->values(),
            $scoreGroups->count(),
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.school.scores.index', [
            'scores'     => $scores,
            'years'      => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters'  => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes'    => SchoolClass::with('academicYear')
                ->where('status', 'active')
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->get(),
            'grades'     => Grade::orderBy('level')->get(),
            'subjects'   => Subject::where('is_active', true)->orderBy('name')->get(),
            'scoreTypes' => [
                'oral'         => 'Miệng',
                'fifteen_minute' => '15 phút',
                'one_period'   => '1 tiết',
                'midterm'      => 'Giữa kỳ',
                'final'        => 'Cuối kỳ',
            ],
        ]);
    }

    public function attendance(Request $request)
    {
        $date = $request->date('date');
        $dateFrom = $request->date('date_from') ?: ($date ?: today());
        $dateTo = $request->date('date_to') ?: ($date ?: today());
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $gradeId = $request->filled('grade_id') ? (int) $request->input('grade_id') : null;
        $subjectId = $request->filled('subject_id') ? (int) $request->input('subject_id') : null;
        $status = trim((string) $request->input('status', ''));
        $records = AttendanceRecord::with(['student', 'class', 'subject'])
            ->whereDate('attended_on', '>=', $dateFrom)
            ->whereDate('attended_on', '<=', $dateTo)
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('student', fn($student) => $student->where('full_name', 'like', "%{$search}%")->orWhere('student_code', 'like', "%{$search}%"))
                    ->orWhereHas('class', fn($class) => $class->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn($subject) => $subject->where('name', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId))
            ->when($classId, fn($query) => $query->where('class_id', $classId))
            ->when($gradeId, fn($query) => $query->whereHas('class', fn($class) => $class->where('grade_id', $gradeId)))
            ->when($subjectId, fn($query) => $query->where('subject_id', $subjectId))
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.school.attendance.index', [
            'records' => $records,
            'date' => $date ?: $dateFrom,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'attendanceStatuses' => ['present' => 'Có mặt', 'absent' => 'Vắng', 'late' => 'Đi muộn', 'excused' => 'Có phép'],
        ]);
    }

    public function schedules(Request $request)
    {
        $currentYear = $this->currentYear();
        $years = AcademicYear::with('semesters')->orderByDesc('starts_on')->get();
        $selectedYearId = $request->filled('academic_year_id')
            ? (int) $request->input('academic_year_id')
            : $currentYear?->id;
        $year = $years->firstWhere('id', $selectedYearId) ?: $currentYear;
        $selectedYearId = $year?->id;
        $semesters = $year?->semesters?->sortBy('number')->values() ?? collect();
        $selectedSemesterId = $request->has('semester_id')
            ? ($request->filled('semester_id') ? (int) $request->input('semester_id') : null)
            : ($semesters->firstWhere('is_current', true)?->id ?: $semesters->first()?->id);

        if ($selectedSemesterId && ! $semesters->contains('id', $selectedSemesterId)) {
            $selectedSemesterId = null;
        }

        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $search = trim((string) $request->input('q', ''));
        $selectedWeekStart = $request->date('week_start')?->startOfWeek() ?? today()->startOfWeek();

        $classQuery = SchoolClass::with('grade')
            ->where('status', 'active')
            ->when($selectedYearId, fn($query) => $query->where('academic_year_id', $selectedYearId))
            ->when($selectedClassId, fn($query) => $query->whereKey($selectedClassId))
            ->orderBy('name');
        // The schedule view renders one grid per class and needs collection
        // operations such as groupBy/whereIn; keep its options as a collection.
        $classOptions = (clone $classQuery)->get();
        $classes = $classOptions;
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $teachers = Teacher::with('subjects')->where('status', 'active')->orderBy('full_name')->get();

        $schedules = Schedule::with(['class.grade', 'subject', 'teacher', 'semester'])
            ->when($selectedYearId, fn($query) => $query->where('academic_year_id', $selectedYearId))
            ->when($selectedSemesterId, fn($query) => $query->where('semester_id', $selectedSemesterId))
            ->whereDate('week_start', $selectedWeekStart)
            ->when($selectedClassId, fn($query) => $query->where('class_id', $selectedClassId))
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('class', fn($class) => $class->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn($subject) => $subject->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('teacher', fn($teacher) => $teacher->where('full_name', 'like', "%{$search}%"))
                    ->orWhere('room', 'like', "%{$search}%");
            }))
            ->orderBy('class_id')
            ->orderBy('weekday')
            ->orderBy('period')
            ->get();

        return view('admin.school.schedules.index', compact(
            'schedules',
            'years',
            'year',
            'semesters',
            'classes',
            'classOptions',
            'selectedWeekStart',
            'selectedYearId',
            'selectedSemesterId',
            'selectedClassId',
            'subjects',
            'teachers'
        ));
    }

    public function storeSchedule(Request $request)
    {
        $data = $this->scheduleData($request);
        Schedule::create($data);

        return to_route('admin.school.schedules.index', [
            'academic_year_id' => $data['academic_year_id'],
            'semester_id' => $data['semester_id'],
            'class_id' => $data['class_id'],
            'week_start' => $data['week_start'],
        ])->with('success', 'Đã thêm tiết học vào thời khóa biểu.');
    }

    public function updateSchedule(Request $request, Schedule $schedule)
    {
        $data = $this->scheduleData($request, $schedule);
        $schedule->update($data);

        return to_route('admin.school.schedules.index', [
            'academic_year_id' => $data['academic_year_id'],
            'semester_id' => $data['semester_id'],
            'class_id' => $data['class_id'],
            'week_start' => $data['week_start'],
        ])->with('success', 'Đã cập nhật tiết học.');
    }

    public function destroySchedule(Schedule $schedule)
    {
        $schedule->delete();

        return back()->with('success', 'Đã xóa tiết học khỏi thời khóa biểu.');
    }

    public function exams(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $semesterId = $request->filled('semester_id') ? (int) $request->input('semester_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $subjectId = $request->filled('subject_id') ? (int) $request->input('subject_id') : null;
        $examType = trim((string) $request->input('exam_type', ''));
        $dateFrom = $request->date('date_from');
        $dateTo = $request->date('date_to');
        $exams = Exam::with(['subject', 'class', 'semester', 'invigilations.teacher'])
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('exam_type', 'like', "%{$search}%")
                    ->orWhere('room', 'like', "%{$search}%")
                    ->orWhereHas('subject', fn($subject) => $subject->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('class', fn($class) => $class->where('name', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId))
            ->when($semesterId, fn($query) => $query->where('semester_id', $semesterId))
            ->when($classId, fn($query) => $query->where('class_id', $classId))
            ->when($subjectId, fn($query) => $query->where('subject_id', $subjectId))
            ->when($examType !== '', fn($query) => $query->where('exam_type', $examType))
            ->when($dateFrom, fn($query) => $query->whereDate('exam_date', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('exam_date', '<=', $dateTo))
            ->orderBy('exam_date')->paginate(20)->withQueryString();

        return view('admin.school.exams.index', [
            'exams' => $exams,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'examTypes' => ['midterm' => 'Giữa kỳ', 'final' => 'Cuối kỳ'],
        ]);
    }

    public function createExam()
    {
        return view('admin.school.exams.form', $this->examFormData());
    }

    public function editExam(Exam $exam)
    {
        return view('admin.school.exams.form', $this->examFormData($exam));
    }

    public function storeExam(Request $request)
    {
        Exam::create($this->examData($request));

        return to_route('admin.school.exams.index')->with('success', 'Đã thêm lịch thi.');
    }

    public function updateExam(Request $request, Exam $exam)
    {
        $exam->update($this->examData($request));

        return to_route('admin.school.exams.index')->with('success', 'Đã cập nhật lịch thi.');
    }

    public function destroyExam(Exam $exam)
    {
        $exam->delete();

        return back()->with('success', 'Đã xóa lịch thi.');
    }

    public function examInvigilations(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $teacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;
        $dateFrom = $request->date('date_from');
        $dateTo = $request->date('date_to');

        $invigilations = ExamInvigilation::with(['exam.academicYear', 'exam.semester', 'exam.subject', 'exam.class', 'teacher'])
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('teacher', fn($teacher) => $teacher
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('teacher_code', 'like', "%{$search}%"))
                    ->orWhereHas('exam', fn($exam) => $exam
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('room', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn($query) => $query->whereHas('exam', fn($exam) => $exam->where('academic_year_id', $yearId)))
            ->when($teacherId, fn($query) => $query->where('teacher_id', $teacherId))
            ->when($dateFrom, fn($query) => $query->whereHas('exam', fn($exam) => $exam->whereDate('exam_date', '>=', $dateFrom)))
            ->when($dateTo, fn($query) => $query->whereHas('exam', fn($exam) => $exam->whereDate('exam_date', '<=', $dateTo)))
            ->join('exams', 'exams.id', '=', 'exam_invigilations.exam_id')
            ->select('exam_invigilations.*')
            ->orderBy('exams.exam_date')
            ->orderBy('exams.starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.school.exam-invigilations.index', [
            'invigilations' => $invigilations,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'teachers' => Teacher::orderBy('full_name')->get(),
            'roles' => $this->invigilationRoles(),
        ]);
    }

    public function createExamInvigilation(Request $request)
    {
        return view('admin.school.exam-invigilations.form', $this->examInvigilationFormData(
            null,
            $request->filled('exam_id') ? (int) $request->input('exam_id') : null
        ));
    }

    public function storeExamInvigilation(Request $request)
    {
        $invigilation = ExamInvigilation::create($this->examInvigilationData($request));
        $this->notifyExamInvigilator($invigilation, 'assigned');

        return to_route('admin.school.exam-invigilations.index')->with('success', 'Đã phân công giáo viên coi thi.');
    }

    public function editExamInvigilation(ExamInvigilation $examInvigilation)
    {
        return view('admin.school.exam-invigilations.form', $this->examInvigilationFormData($examInvigilation));
    }

    public function updateExamInvigilation(Request $request, ExamInvigilation $examInvigilation)
    {
        $examInvigilation->update($this->examInvigilationData($request, $examInvigilation));
        $this->notifyExamInvigilator($examInvigilation, 'updated');

        return to_route('admin.school.exam-invigilations.index')->with('success', 'Đã cập nhật phân công coi thi.');
    }

    public function destroyExamInvigilation(ExamInvigilation $examInvigilation)
    {
        $examInvigilation->delete();

        return back()->with('success', 'Đã xóa phân công coi thi.');
    }

    private function notifyExamInvigilator(ExamInvigilation $invigilation, string $action): void
    {
        $invigilation->load(['exam.subject', 'exam.class', 'teacher.user']);
        $invigilation->teacher?->user?->notify(new ExamInvigilationAssigned($invigilation, $action));
    }

    public function announcements(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $audience = trim((string) $request->input('audience', ''));
        $type = trim((string) $request->input('type', ''));
        $published = $request->input('published', '');
        $announcements = Announcement::when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('audience', 'like', "%{$search}%");
        }))
            ->when($audience !== '', fn($query) => $query->where('audience', $audience))
            ->when($type !== '', fn($query) => $query->where('type', $type))
            ->when($published !== '', fn($query) => $query->where('is_published', $published === 'published'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.school.announcements.index', [
            'announcements' => $announcements,
            'audiences' => ['all' => 'Toàn trường', 'students' => 'Học sinh', 'teachers' => 'Giáo viên'],
            'announcementTypes' => Announcement::query()->select('type')->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function createAnnouncement()
    {
        return view('admin.school.announcements.form', ['announcement' => null]);
    }

    public function editAnnouncement(Announcement $announcement)
    {
        return view('admin.school.announcements.form', compact('announcement'));
    }

    public function storeAnnouncement(Request $request)
    {
        Announcement::create($this->announcementData($request) + ['author_id' => auth()->id()]);

        return to_route('admin.school.announcements.index')->with('success', 'Đã tạo thông báo.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement)
    {
        $announcement->update($this->announcementData($request));

        return to_route('admin.school.announcements.index')->with('success', 'Đã cập nhật thông báo.');
    }

    public function destroyAnnouncement(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', 'Đã xóa thông báo.');
    }

    public function fees(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $semesterId = $request->filled('semester_id') ? (int) $request->input('semester_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $active = $request->input('active', '');
        $fees = FeeNotice::with(['payments'])
            ->withCount('payments')
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($yearId, fn($query) => $query->where('academic_year_id', $yearId))
            ->when($semesterId, fn($query) => $query->where('semester_id', $semesterId))
            ->when($classId, fn($query) => $query->where('class_id', $classId))
            ->when($active !== '', fn($query) => $query->where('is_active', $active === 'active'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.school.fees.index', [
            'fees' => $fees,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    public function createFee()
    {
        return view('admin.school.fees.form', $this->feeFormData());
    }

    public function editFee(FeeNotice $fee)
    {
        return view('admin.school.fees.form', $this->feeFormData($fee));
    }

    public function storeFee(Request $request)
    {
        FeeNotice::create($this->feeData($request));

        return to_route('admin.school.fees.index')->with('success', 'Đã tạo khoản thu học phí.');
    }

    public function updateFee(Request $request, FeeNotice $fee)
    {
        $fee->update($this->feeData($request));

        return to_route('admin.school.fees.index')->with('success', 'Đã cập nhật khoản thu học phí.');
    }

    public function destroyFee(FeeNotice $fee)
    {
        $fee->delete();

        return back()->with('success', 'Đã xóa khoản thu học phí.');
    }

    private function examFormData(?Exam $exam = null): array
    {
        return [
            'exam' => $exam,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ];
    }

    private function examData(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:255'],
            'exam_type' => ['required', 'string', 'max:30'],
            'exam_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function examInvigilationFormData(?ExamInvigilation $examInvigilation = null, ?int $selectedExamId = null): array
    {
        return [
            'examInvigilation' => $examInvigilation,
            'selectedExamId' => $selectedExamId,
            'exams' => Exam::with(['academicYear', 'semester', 'subject', 'class'])
                ->orderByDesc('exam_date')
                ->orderBy('starts_at')
                ->get(),
            'teachers' => Teacher::where(function ($query) use ($examInvigilation): void {
                $query->where('status', 'active')
                    ->when($examInvigilation, fn($query) => $query->orWhere('id', $examInvigilation->teacher_id));
            })->orderBy('full_name')->get(),
            'roles' => $this->invigilationRoles(),
        ];
    }

    private function examInvigilationData(Request $request, ?ExamInvigilation $examInvigilation = null): array
    {
        $data = $request->validate([
            'exam_id' => ['required', 'exists:exams,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'role' => ['required', Rule::in(array_keys($this->invigilationRoles()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacher = Teacher::findOrFail($data['teacher_id']);
        $isNewAssignment = ! $examInvigilation
            || $examInvigilation->teacher_id !== (int) $data['teacher_id']
            || $examInvigilation->exam_id !== (int) $data['exam_id'];
        if ($isNewAssignment && $teacher->status !== 'active') {
            throw ValidationException::withMessages(['teacher_id' => 'Chỉ giáo viên đang công tác mới được phân công coi thi.']);
        }

        $duplicate = ExamInvigilation::where('exam_id', $data['exam_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->when($examInvigilation, fn($query) => $query->whereKeyNot($examInvigilation->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['teacher_id' => 'Giáo viên đã được phân công trong kỳ thi này.']);
        }

        $exam = Exam::findOrFail($data['exam_id']);
        $conflict = ExamInvigilation::where('teacher_id', $data['teacher_id'])
            ->when($examInvigilation, fn($query) => $query->whereKeyNot($examInvigilation->id))
            ->whereHas('exam', function ($query) use ($exam): void {
                $query->whereKeyNot($exam->id)
                    ->whereDate('exam_date', $exam->exam_date);

                if ($exam->starts_at && $exam->ends_at) {
                    $query->where(function ($query) use ($exam): void {
                        $query->whereNull('starts_at')
                            ->orWhereNull('ends_at')
                            ->orWhere(function ($query) use ($exam): void {
                                $query->where('starts_at', '<', $exam->ends_at)
                                    ->where('ends_at', '>', $exam->starts_at);
                            });
                    });
                }
            })
            ->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['teacher_id' => 'Giáo viên đã có lịch coi thi khác trùng ngày và thời gian.']);
        }

        return $data;
    }

    private function invigilationRoles(): array
    {
        return ['chief' => 'Cán bộ coi thi 1', 'invigilator' => 'Cán bộ coi thi 2', 'reserve' => 'Giáo viên dự phòng'];
    }

    private function announcementData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'string', 'max:30'],
            'audience' => ['required', 'string', 'max:30'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]) + ['is_published' => $request->boolean('is_published')];
    }

    private function feeFormData(?FeeNotice $fee = null): array
    {
        return [
            'fee' => $fee,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ];
    }

    private function feeData(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    private function scheduleData(Request $request, ?Schedule $schedule = null): array
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'week_start' => ['nullable', 'date'],
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'weekday' => ['required', 'integer', 'between:1,6'],
            'period' => ['required', 'integer', 'between:1,12'],
            'room' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['week_start'] = \Illuminate\Support\Carbon::parse($data['week_start'] ?? today()->toDateString())
            ->startOfWeek()
            ->toDateString();

        if (! Semester::whereKey($data['semester_id'])->where('academic_year_id', $data['academic_year_id'])->exists()) {
            throw ValidationException::withMessages(['semester_id' => 'Học kỳ không thuộc năm học đã chọn.']);
        }
        if (! SchoolClass::whereKey($data['class_id'])->where('academic_year_id', $data['academic_year_id'])->exists()) {
            throw ValidationException::withMessages(['class_id' => 'Lớp không thuộc năm học đã chọn.']);
        }
        if (! empty($data['teacher_id']) && ! DB::table('teacher_subject')
            ->where('teacher_id', $data['teacher_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->exists()) {
            throw ValidationException::withMessages(['teacher_id' => 'Giáo viên chưa được khai báo giảng dạy môn này trong năm học đã chọn.']);
        }
        if (! empty($data['teacher_id']) && ! Teacher::whereKey($data['teacher_id'])->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['teacher_id' => 'Chỉ giáo viên đang công tác mới được xếp lịch giảng dạy.']);
        }

        $classConflict = Schedule::where('semester_id', $data['semester_id'])
            ->where('class_id', $data['class_id'])
            ->whereDate('week_start', $data['week_start'])
            ->where('weekday', $data['weekday'])
            ->where('period', $data['period'])
            ->when($schedule, fn($query) => $query->whereKeyNot($schedule->id))
            ->exists();
        if ($classConflict) {
            throw ValidationException::withMessages(['period' => 'Lớp đã có môn học ở thứ và tiết này.']);
        }

        if (! empty($data['teacher_id'])) {
            $teacherConflict = Schedule::where('semester_id', $data['semester_id'])
                ->where('teacher_id', $data['teacher_id'])
                ->whereDate('week_start', $data['week_start'])
                ->where('weekday', $data['weekday'])
                ->where('period', $data['period'])
                ->when($schedule, fn($query) => $query->whereKeyNot($schedule->id))
                ->exists();
            if ($teacherConflict) {
                throw ValidationException::withMessages(['teacher_id' => 'Giáo viên đã có lịch dạy ở lớp khác trong tiết này.']);
            }
        }

        return $data;
    }

    private function currentYear(): ?AcademicYear
    {
        return AcademicYear::where('is_current', true)->with('semesters')->first()
            ?? AcademicYear::latest('starts_on')->with('semesters')->first();
    }

    private function validateHomeroomTeacher(?int $teacherId, int $academicYearId, ?int $classId = null, ?int $currentTeacherId = null): void
    {
        if (! $teacherId) {
            return;
        }

        if (! Teacher::whereKey($teacherId)->where('status', 'active')->exists()) {
            if ($teacherId === $currentTeacherId) {
                return;
            }
            throw ValidationException::withMessages(['homeroom_teacher_id' => 'Chỉ giáo viên đang công tác mới được phân công chủ nhiệm.']);
        }

        $alreadyAssigned = SchoolClass::where('academic_year_id', $academicYearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->when($classId, fn($query) => $query->whereKeyNot($classId))
            ->exists();
        if ($alreadyAssigned) {
            throw ValidationException::withMessages(['homeroom_teacher_id' => 'Giáo viên này đã là chủ nhiệm của một lớp khác trong năm học đã chọn.']);
        }
    }

    private function resolveAcademicYear(?int $yearId, ?string $name): AcademicYear
    {
        if ($yearId) {
            return AcademicYear::findOrFail($yearId);
        }

        $name = trim((string) $name);
        if (! preg_match('/^(\d{4})\s*[-\/]\s*(\d{4})$/', $name, $matches)) {
            abort(422, 'Năm học phải có dạng YYYY-YYYY, ví dụ 2027-2028.');
        }

        $start = (int) $matches[1];
        $end = (int) $matches[2];
        if ($end !== $start + 1) {
            abort(422, 'Năm kết thúc phải liền sau năm bắt đầu.');
        }

        $year = AcademicYear::firstOrCreate(
            ['code' => $start . '-' . $end],
            ['name' => $start . '-' . $end, 'starts_on' => $start . '-08-15', 'ends_on' => $end . '-05-31', 'is_current' => false]
        );
        Semester::firstOrCreate(
            ['academic_year_id' => $year->id, 'number' => 1],
            ['name' => 'Semester 1', 'starts_on' => $start . '-08-15', 'ends_on' => $start . '-12-31', 'is_current' => false]
        );
        Semester::firstOrCreate(
            ['academic_year_id' => $year->id, 'number' => 2],
            ['name' => 'Semester 2', 'starts_on' => $end . '-01-01', 'ends_on' => $end . '-05-31', 'is_current' => false]
        );

        return $year;
    }

    private function studentData(Request $request, ?Student $student = null): array
    {
        $emailRules = ['email', 'max:255'];
        $emailRules[] = $student ? 'nullable' : 'required';
        if (! $student) {
            $emailRules[] = 'unique:users,email';
        }

        $data = $request->validate([
            'student_code' => ['required', 'string', 'max:30', $student ? Rule::unique('students', 'student_code')->ignore($student) : 'unique:students,student_code'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'admission_year' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['studying', 'sick_leave', 'reserved', 'dropped', 'graduated'])],
            'notes' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
        ]);

        if ($data['status'] !== 'studying') {
            $data['academic_year_id'] = null;
            $data['class_id'] = null;
        }

        $enrollment = ['academic_year_id' => $data['academic_year_id'] ?? null, 'class_id' => $data['class_id'] ?? null];
        $avatar = $data['avatar'] ?? null;
        unset($data['avatar']);
        unset($data['academic_year_id'], $data['class_id']);

        return ['student' => $data, 'avatar' => $avatar, ...$enrollment];
    }

    private function syncEnrollment(Student $student, ?int $yearId, ?int $classId): void
    {
        if ($student->status !== 'studying') {
            $student->enrollments()->where('status', 'active')->update([
                'status' => 'inactive',
                'left_on' => today(),
            ]);

            return;
        }
        if (! $yearId && ! $classId) {
            return;
        }
        if (! $yearId || ! $classId) {
            throw ValidationException::withMessages(['class_id' => 'Học sinh đang đi học phải được chọn đủ năm học và lớp.']);
        }
        if (! SchoolClass::whereKey($classId)->where('academic_year_id', $yearId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['class_id' => 'Lớp phải thuộc đúng năm học và đang hoạt động.']);
        }

        $activeEnrollment = $student->enrollments()
            ->where('academic_year_id', $yearId)
            ->where('status', 'active')
            ->first();
        if ($activeEnrollment && (int) $activeEnrollment->class_id !== (int) $classId) {
            throw ValidationException::withMessages([
                'class_id' => 'Học sinh đã thuộc lớp đang học trong năm này. Không thể chuyển sang lớp khác.',
            ]);
        }

        $student->enrollments()->updateOrCreate(
            ['academic_year_id' => $yearId],
            ['class_id' => $classId, 'status' => 'active', 'enrolled_on' => today(), 'left_on' => null]
        );
    }

    private function teacherData(Request $request, ?Teacher $teacher = null): array
    {
        $emailRules = ['email', 'max:255'];
        $emailRules[] = $teacher ? 'nullable' : 'required';
        if (! $teacher) {
            $emailRules[] = 'unique:users,email';
        }

        $data = $request->validate([
            'teacher_code' => ['required', 'string', 'max:30', $teacher ? Rule::unique('teachers', 'teacher_code')->ignore($teacher) : 'unique:teachers,teacher_code'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'on_leave', 'inactive'])],
            'notes' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
        unset($data['avatar']);

        return $data;
    }

    private function storeTeacherAvatar(Request $request, Teacher $teacher): void
    {
        if (! $request->hasFile('avatar')) {
            return;
        }
        if ($teacher->avatar_path) {
            Storage::disk('public')->delete($teacher->avatar_path);
        }
        $teacher->update(['avatar_path' => $request->file('avatar')->store('avatars/teachers', 'public')]);
    }

    private function syncTeacherProfile(Request $request, Teacher $teacher): void
    {
        $data = $request->validate([
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['exists:subjects,id'],
            'homeroom_class_ids' => ['nullable', 'array'],
            'homeroom_class_ids.*' => ['exists:classes,id'],
            'is_homeroom_teacher' => ['nullable', 'boolean'],
        ]);
        $yearId = (int) ($data['academic_year_id'] ?? $this->currentYear()?->id);
        if (! $yearId) {
            throw ValidationException::withMessages(['academic_year_id' => 'Hệ thống chưa có năm học để lưu môn giảng dạy.']);
        }
        $subjectIds = collect($data['subjects'] ?? [])->map(fn($id) => (int) $id)->values();
        $homeroomClassIds = collect($data['homeroom_class_ids'] ?? [])->map(fn($id) => (int) $id)->unique()->values();

        $existingHomeroomIds = $teacher->homeroomClasses()
            ->where('academic_year_id', $yearId)
            ->pluck('id')
            ->map(fn($id) => (int) $id);
        $hasNewHomeroomAssignment = $homeroomClassIds->diff($existingHomeroomIds)->isNotEmpty();
        if ($teacher->status !== 'active' && $request->boolean('is_homeroom_teacher') && $hasNewHomeroomAssignment) {
            throw ValidationException::withMessages(['status' => 'Giáo viên tạm nghỉ hoặc ngừng công tác không thể nhận phân công chủ nhiệm.']);
        }
        if ($homeroomClassIds->count() > 1) {
            throw ValidationException::withMessages(['homeroom_class_ids' => 'Một giáo viên chỉ được làm chủ nhiệm một lớp trong cùng năm học.']);
        }
        foreach ($homeroomClassIds as $homeroomClassId) {
            $class = SchoolClass::whereKey($homeroomClassId)->where('academic_year_id', $yearId)->first();
            if (! $class) {
                throw ValidationException::withMessages(['homeroom_class_ids' => 'Lớp chủ nhiệm phải thuộc năm học đã chọn.']);
            }
            if ($class->homeroom_teacher_id && $class->homeroom_teacher_id !== $teacher->id) {
                throw ValidationException::withMessages(['homeroom_class_ids' => "Lớp {$class->name} đã có giáo viên chủ nhiệm."]);
            }
            $this->validateHomeroomTeacher($teacher->id, $yearId, $class->id, $teacher->id);
        }

        DB::table('teacher_subject')
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $yearId)
            ->delete();
        if ($subjectIds->isNotEmpty()) {
            $now = now();
            DB::table('teacher_subject')->insert(
                $subjectIds->map(fn(int $subjectId) => [
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subjectId,
                    'academic_year_id' => $yearId,
                    'is_primary' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }

        SchoolClass::where('academic_year_id', $yearId)
            ->where('homeroom_teacher_id', $teacher->id)
            ->update(['homeroom_teacher_id' => null]);

        if ($request->boolean('is_homeroom_teacher')) {
            SchoolClass::where('academic_year_id', $yearId)
                ->whereIn('id', $homeroomClassIds)
                ->update(['homeroom_teacher_id' => $teacher->id]);
        }
    }

    private function createStudentAccount(Student $student): User
    {
        $user = User::create([
            'name' => $student->full_name,
            'email' => $student->email,
            'password' => Hash::make('123456'),
            'must_change_password' => true,
            'avatar_path' => $student->avatar_path,
        ]);
        $studentRole = Role::where('slug', 'student')->firstOrFail();
        $user->roles()->sync([$studentRole->id]);
        $student->update(['user_id' => $user->id]);

        return $user;
    }

    private function createTeacherAccount(Request $request, Teacher $teacher): User
    {
        $user = User::create([
            'name' => $teacher->full_name,
            'email' => $teacher->email,
            'password' => Hash::make('123456'),
            'must_change_password' => true,
            'avatar_path' => $teacher->avatar_path,
        ]);

        $roleSlugs = ['teacher'];
        if ($request->boolean('is_homeroom_teacher')) {
            $roleSlugs[] = 'homeroom_teacher';
        }
        if (filled($request->input('subjects'))) {
            $roleSlugs[] = 'subject_teacher';
        }
        $roleIds = Role::whereIn('slug', array_unique($roleSlugs))->pluck('id');
        $user->roles()->sync($roleIds);
        $teacher->update(['user_id' => $user->id]);

        return $user;
    }
}
