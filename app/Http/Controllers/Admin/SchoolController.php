<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreStudentRequest;
use App\Http\Requests\School\StoreTeacherRequest;
use App\Http\Requests\School\StoreTeachingAssignmentRequest;
use App\Http\Requests\School\UpdateStudentRequest;
use App\Http\Requests\School\UpdateTeacherRequest;
use App\Http\Requests\School\UpdateTeachingAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\ConductEvaluation;
use App\Models\Exam;
use App\Models\ExamInvigilation;
use App\Models\ExamResult;
use App\Models\FeeAssessment;
use App\Models\FeeNotice;
use App\Models\FeePayment;
use App\Models\Grade;
use App\Models\Guardian;
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
use App\Services\HomeroomAssignmentService;
use App\Services\StudentService;
use App\Services\TeacherService;
use App\Services\TeachingAssignmentService;
use App\Support\Decimal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolController extends Controller
{
    /**
     * Trang tổng quan trường học: thống kê số liệu học sinh, học phí, điểm số, attendance và kế hoạch giảng dạy.
     */
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

        $classScope = SchoolClass::query()
            ->where('status', 'active')
            ->when($filterAcademicYearId, fn ($query) => $query->where('academic_year_id', $filterAcademicYearId))
            ->when($filterGradeId, fn ($query) => $query->where('grade_id', $filterGradeId))
            ->when($filterClassId, fn ($query) => $query->whereKey($filterClassId));

        $classesFilter = DB::table('classes')
            ->select('id', 'name', 'grade_id')
            ->where('status', 'active')
            ->when($filterAcademicYearId, fn ($query) => $query->where('academic_year_id', $filterAcademicYearId))
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

        $filteredClassIds = (clone $classScope)->pluck('id');

        $filteredStudentIds = (clone $studentQuery)
            ->select('enrollments.student_id')
            ->distinct()
            ->pluck('enrollments.student_id');

        $activeStudents = (clone $studentQuery)
            ->distinct('enrollments.student_id')
            ->count('enrollments.student_id');

        $activeClasses = $filteredClassIds->count();

        $studentCountsByClass = (clone $studentQuery)
            ->select('classes.id', DB::raw('count(distinct enrollments.student_id) as total'))
            ->groupBy('classes.id')
            ->pluck('total', 'classes.id');

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
                ->whereIn('class_id', $filteredClassIds)
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

        $attendanceDetails = $filterAcademicYearId
            ? AttendanceRecord::with(['student', 'class', 'subject'])
                ->where('academic_year_id', $filterAcademicYearId)
                ->whereIn('class_id', $filteredClassIds)
                ->whereBetween('attended_on', [
                    $filterDateFrom->toDateString(),
                    $filterDateTo->toDateString(),
                ])
                ->whereIn('status', ['absent', 'late', 'excused'])
                ->latest('attended_on')
                ->latest('id')
                ->limit(50)
                ->get()
            : collect();

        $attendanceTrend = $filterAcademicYearId
            ? AttendanceRecord::where('academic_year_id', $filterAcademicYearId)
                ->whereIn('class_id', $filteredClassIds)
                ->whereBetween('attended_on', [
                    $filterDateFrom->toDateString(),
                    $filterDateTo->toDateString(),
                ])
                ->select('attended_on', 'status', DB::raw('count(*) as total'))
                ->groupBy('attended_on', 'status')
                ->orderBy('attended_on')
                ->get()
                ->groupBy(fn ($row) => Carbon::parse($row->attended_on)->format('Y-m-d'))
                ->map(function ($rows, string $date): array {
                    $counts = $rows->pluck('total', 'status');

                    return [
                        'date' => $date,
                        'present' => (int) ($counts['present'] ?? 0),
                        'absent' => (int) ($counts['absent'] ?? 0),
                        'late' => (int) ($counts['late'] ?? 0),
                        'excused' => (int) ($counts['excused'] ?? 0),
                        'total' => (int) $counts->sum(),
                    ];
                })
                ->take(-14)
                ->values()
            : collect();

        $teachers = Teacher::where('status', 'active')
            ->when($filterAcademicYearId, function ($query) use ($filterAcademicYearId, $filteredClassIds): void {
                $query->where(function ($query) use ($filterAcademicYearId, $filteredClassIds): void {
                    $query->whereHas('assignments', fn ($assignment) => $assignment
                        ->where('academic_year_id', $filterAcademicYearId)
                        ->whereIn('class_id', $filteredClassIds))
                        ->orWhereHas('homeroomClasses', fn ($class) => $class
                            ->where('academic_year_id', $filterAcademicYearId)
                            ->whereIn('id', $filteredClassIds));
                });
            })
            ->count();

        $subjects = Subject::where('is_active', true)->count();

        $scoreAverageDetails = $filterAcademicYearId
            ? Score::join('students', 'students.id', '=', 'scores.student_id')
                ->where('scores.academic_year_id', $filterAcademicYearId)
                ->whereIn('scores.student_id', $filteredStudentIds)
                ->select(
                    'scores.student_id',
                    'students.student_code',
                    'students.full_name',
                    DB::raw('avg(scores.score) as average_score'),
                    DB::raw('count(scores.id) as score_count')
                )
                ->groupBy('scores.student_id', 'students.student_code', 'students.full_name')
                ->orderByDesc('average_score')
                ->get()
            : collect();

        $scoreRatings = collect([
            'excellent' => $scoreAverageDetails->where('average_score', '>=', 8)->count(),
            'good' => $scoreAverageDetails->filter(fn ($row) => $row->average_score >= 6.5 && $row->average_score < 8)->count(),
            'average' => $scoreAverageDetails->filter(fn ($row) => $row->average_score >= 5 && $row->average_score < 6.5)->count(),
            'weak' => $scoreAverageDetails->where('average_score', '<', 5)->count(),
        ]);

        $scoreCount = $filterAcademicYearId
            ? Score::where('academic_year_id', $filterAcademicYearId)
                ->whereIn('class_id', $filteredClassIds)
                ->count()
            : 0;

        $financeSchemaReady = Schema::hasTable('fee_assessments');
        $feeNotices = $filterAcademicYearId
            ? FeeNotice::with($financeSchemaReady ? [
                'class.grade',
                'assessments' => fn ($query) => $query
                    ->whereIn('student_id', $filteredStudentIds)
                    ->where('status', '!=', 'cancelled'),
            ] : [
                'class.grade',
                'payments' => fn ($query) => $query->whereIn('student_id', $filteredStudentIds),
            ])
                ->where('academic_year_id', $filterAcademicYearId)
                ->where('is_active', true)
                ->where(function ($query) use ($filteredClassIds): void {
                    $query->whereNull('class_id')->orWhereIn('class_id', $filteredClassIds);
                })
                ->orderBy('due_on')
                ->get()
            : collect();

        $feeBreakdown = $feeNotices->map(function (FeeNotice $notice) use (
            $activeStudents,
            $studentCountsByClass,
            $financeSchemaReady
        ): array {
            if ($financeSchemaReady && ! $notice->isLegacy()) {
                $assessments = $notice->assessments;
                $expected = Decimal::sum($assessments->pluck('final_amount'));
                $paid = Decimal::sum($assessments->pluck('paid_amount'));
                $outstanding = Decimal::sum($assessments->pluck('remaining_amount'));

                return [
                    'notice' => $notice,
                    'student_count' => $assessments->pluck('student_id')->unique()->count(),
                    'paid_students' => $assessments->where('status', 'paid')->pluck('student_id')->unique()->count(),
                    'expected' => $expected,
                    'paid' => $paid,
                    'outstanding' => $outstanding,
                    'overdue' => $notice->due_on?->isPast() && Decimal::isPositive($outstanding),
                ];
            }

            $studentCount = $notice->class_id
                ? (int) ($studentCountsByClass[$notice->class_id] ?? 0)
                : (int) $activeStudents;
            $expected = Decimal::multiply($notice->amount, $studentCount);
            $paidPayments = $notice->payments->where('status', 'paid');
            $paid = Decimal::sum($paidPayments->pluck('amount'));
            $outstanding = Decimal::max('0.00', Decimal::subtract($expected, $paid));

            return [
                'notice' => $notice,
                'student_count' => $studentCount,
                'paid_students' => $paidPayments->pluck('student_id')->unique()->count(),
                'expected' => $expected,
                'paid' => $paid,
                'outstanding' => $outstanding,
                'overdue' => $notice->due_on?->isPast() && Decimal::isPositive($outstanding),
            ];
        });

        $paidFees = Decimal::sum($feeBreakdown->pluck('paid'));
        $outstandingFees = Decimal::sum($feeBreakdown->pluck('outstanding'));

        $classOverview = (clone $classScope)
            ->with(['grade', 'homeroomTeacher'])
            ->withCount([
                'enrollments as active_students_count' => fn ($query) => $query
                    ->where('academic_year_id', $filterAcademicYearId)
                    ->where('status', 'active'),
                'assignments as active_assignments_count' => fn ($query) => $query
                    ->where('academic_year_id', $filterAcademicYearId)
                    ->where('status', 'active'),
                'schedules as scheduled_periods_count' => fn ($query) => $query
                    ->where('academic_year_id', $filterAcademicYearId),
            ])
            ->orderBy('name')
            ->get();

        $classesWithoutHomeroom = $classOverview->whereNull('homeroom_teacher_id')->count();
        $classesWithoutAssignments = $classOverview->where('active_assignments_count', 0)->count();
        $activeAssignments = TeachingAssignment::where('academic_year_id', $filterAcademicYearId)
            ->whereIn('class_id', $filteredClassIds)
            ->where('status', 'active')
            ->count();
        $unassignedSchedules = Schedule::where('academic_year_id', $filterAcademicYearId)
            ->whereIn('class_id', $filteredClassIds)
            ->whereNull('teacher_id')
            ->whereDate('week_start', '>=', today()->startOfWeek())
            ->count();

        $upcomingExamsQuery = Exam::with([
            'subject',
            'class',
        ])
            ->when(
                $filterAcademicYearId,
                fn ($query) => $query->where(
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
            ->limit(30);

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
                fn ($query) => $query->where(
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

        $recentAnnouncements = Announcement::with('class')->where(
            'is_published',
            true
        )
            ->latest('published_at')
            ->limit(8)
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
            'attendanceDetails' => $attendanceDetails,
            'attendanceTrend' => $attendanceTrend,
            'gradeBreakdown' => $gradeBreakdown,
            'scoreRatings' => $scoreRatings,
            'scoreAverageDetails' => $scoreAverageDetails->take(50),
            'classOverview' => $classOverview,

            'paidFees' => $paidFees,
            'outstandingFees' => $outstandingFees,
            'feeBreakdown' => $feeBreakdown,

            'scoreCount' => $scoreCount,
            'examCount' => $examCount,
            'activeAssignments' => $activeAssignments,
            'classesWithoutHomeroom' => $classesWithoutHomeroom,
            'classesWithoutAssignments' => $classesWithoutAssignments,
            'unassignedSchedules' => $unassignedSchedules,
        ]);
    }

    /**
     * Hiển thị danh sách học sinh theo bộ lọc tìm kiếm, năm học và lớp.
     */
    public function students(Request $request, StudentService $studentService)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $status = trim((string) $request->input('status', ''));
        $admissionYear = trim((string) $request->input('admission_year', ''));
        $students = Student::with(['enrollments' => fn ($query) => $query
            ->with('class.grade', 'academicYear')
            ->where('status', 'active')
            ->latest('id')])
            ->withExists(StudentService::historyExistenceColumns())
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('student_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($admissionYear !== '', fn ($query) => $query->where('admission_year', $admissionYear))
            ->when($yearId || $classId, fn ($query) => $query->whereHas('enrollments', function ($enrollment) use ($yearId, $classId): void {
                $enrollment->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                    ->when($classId, fn ($q) => $q->where('class_id', $classId));
            }))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();
        $studentService->decorateDeletionEligibility($students->getCollection());

        return view('admin.school.students.index', [
            'students' => $students,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with(['academicYear', 'grade'])->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'studentStatuses' => Student::STATUS_LABELS,
            'admissionYears' => Student::query()->whereNotNull('admission_year')->distinct()->orderByDesc('admission_year')->pluck('admission_year'),
        ]);
    }

    /**
     * Mở form tạo hồ sơ học sinh mới.
     */
    public function createStudent()
    {
        return view('admin.school.students.form', [
            'student' => null,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    /**
     * Lưu thông tin học sinh mới và tạo tài khoản đăng nhập mặc định cho học sinh.
     */
    public function storeStudent(StoreStudentRequest $request, StudentService $studentService)
    {
        $student = $studentService->create($request->validated());

        return to_route('admin.school.students.index')->with('success', "Đã tạo học sinh và tài khoản {$student->email}. Mật khẩu mặc định: 123456. Học sinh phải đổi mật khẩu khi đăng nhập lần đầu.");
    }

    /**
     * Hiển thị chi tiết hồ sơ học sinh và dữ liệu liên quan như học phí, điểm, phụ huynh.
     */
    public function showStudent(Student $student, StudentService $studentService)
    {
        return view('admin.school.students.show', $studentService->detailData($student));
    }

    /**
     * Mở form chỉnh sửa hồ sơ học sinh cùng dữ liệu tuyển sinh và phụ huynh đang hoạt động.
     */
    public function editStudent(Student $student, StudentService $studentService)
    {
        $student->load([
            'enrollments' => fn ($query) => $query->where('status', 'active')->latest('academic_year_id'),
            'guardians',
        ]);

        return view('admin.school.students.form', [
            'student' => $student,
            'canGraduate' => $student->status === Student::STATUS_GRADUATED || $studentService->canGraduate($student),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    /**
     * Cập nhật hồ sơ học sinh và đồng bộ thông tin nhập học, phụ huynh và tài khoản liên quan.
     */
    public function updateStudent(UpdateStudentRequest $request, Student $student, StudentService $studentService)
    {
        $studentService->update($student, $request->validated());

        return to_route('admin.school.students.index')->with('success', 'Đã cập nhật hồ sơ học sinh.');
    }

    /**
     * Xóa hồ sơ học sinh sau khi kiểm tra điều kiện đủ tiêu chí và tiến hành xử lý nghiệp vụ liên quan.
     */
    public function destroyStudent(Student $student, StudentService $studentService)
    {
        $studentService->delete($student);

        return back()->with('success', 'Đã xóa học sinh.');
    }

    /**
     * Hiển thị lịch sử thu học phí của học sinh ở chế độ legacy hoặc chuyển hướng sang finance module mới.
     */
    public function studentPayments(Student $student)
    {
        if (Schema::hasTable('fee_assessments') && Schema::hasColumn('fee_payments', 'fee_assessment_id')) {
            return to_route('admin.finance.fees.students.show', $student);
        }

        $student->load(['enrollments.class.grade', 'payments.feeNotice.academicYear', 'payments.feeNotice.semester']);
        $year = $this->currentYear();
        $classId = $student->enrollments()->where('academic_year_id', $year?->id)->value('class_id');
        $notices = FeeNotice::where('academic_year_id', $year?->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('class_id')->orWhere('class_id', $classId))
            ->with(['payments' => fn ($query) => $query->where('student_id', $student->id)])
            ->latest()
            ->get();

        $legacyReadOnly = Schema::hasColumn('fee_payments', 'fee_assessment_id');

        return view('admin.school.students.payments', compact('student', 'year', 'notices', 'legacyReadOnly'));
    }

    /**
     * Ghi nhận hoặc cập nhật giao dịch thanh toán học phí cho học sinh.
     */
    public function storeStudentPayment(Request $request, Student $student)
    {
        if (Schema::hasColumn('fee_payments', 'fee_assessment_id')) {
            throw ValidationException::withMessages([
                'payment' => 'Giao dịch legacy chỉ được tra cứu. Hãy thu tiền từ fee assessment mới.',
            ]);
        }

        $data = $request->validate([
            'fee_notice_id' => ['required', 'exists:fee_notices,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['unpaid', 'pending', 'paid', 'refunded'])],
            'paid_at' => ['nullable', 'date'],
            'transaction_code' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string'],
        ]);
        $notice = FeeNotice::findOrFail($data['fee_notice_id']);
        $enrollment = $student->enrollments()
            ->where('academic_year_id', $notice->academic_year_id)
            ->where('status', 'active')
            ->first();
        if (! $enrollment || ($notice->class_id && (int) $notice->class_id !== (int) $enrollment->class_id)) {
            throw ValidationException::withMessages([
                'fee_notice_id' => 'Khoản thu không áp dụng cho năm học hoặc lớp hiện tại của học sinh.',
            ]);
        }
        if ($data['status'] === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }
        FeePayment::updateOrCreate(
            ['fee_notice_id' => $data['fee_notice_id'], 'student_id' => $student->id],
            $data + ['student_id' => $student->id]
        );

        return back()->with('success', 'Đã cập nhật lịch sử đóng tiền.');
    }

    /**
     * Hiển thị bảng điểm, kết quả học tập, đánh giá hạnh kiểm và thống kê điểm danh theo từng năm học của học sinh.
     */
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

    /**
     * Hiển thị danh sách giáo viên với bộ lọc tìm kiếm, trạng thái, phân loại và năm học.
     */
    public function teachers(Request $request, TeacherService $teacherService)
    {
        $search = trim((string) $request->input('q', ''));
        $status = trim((string) $request->input('status', ''));
        $type = trim((string) $request->input('type', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $teachers = Teacher::withCount(['homeroomClasses', 'assignments'])
            ->withExists(TeacherService::historyExistenceColumns())
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('teacher_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($yearId || $classId, function ($query) use ($yearId, $classId): void {
                $query->where(function ($query) use ($yearId, $classId): void {
                    $query->whereHas('assignments', function ($assignment) use ($yearId, $classId): void {
                        $assignment->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
                            ->when($classId, fn ($query) => $query->where('class_id', $classId));
                    })->orWhereHas('homeroomClasses', function ($class) use ($yearId, $classId): void {
                        $class->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
                            ->when($classId, fn ($query) => $query->whereKey($classId));
                    });
                });
            })
            ->when($type === 'homeroom', fn ($query) => $query->whereHas('homeroomClasses', function ($class) use ($yearId, $classId): void {
                $class->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
                    ->when($classId, fn ($query) => $query->whereKey($classId));
            }))
            ->when($type === 'subject', fn ($query) => $query->whereHas('assignments', function ($assignment) use ($yearId, $classId): void {
                $assignment->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
                    ->when($classId, fn ($query) => $query->where('class_id', $classId));
            }))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();
        $teacherService->decorateDeletionEligibility($teachers->getCollection());

        return view('admin.school.teachers.index', [
            'teachers' => $teachers,
            'teacherStatuses' => Teacher::STATUS_LABELS,
            'teacherType' => $type,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    /**
     * Mở form tạo mới hồ sơ giáo viên.
     */
    public function createTeacher()
    {
        return view('admin.school.teachers.form', [
            'teacher' => null,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    /**
     * Liệt kê giáo viên làm chủ nhiệm theo tìm kiếm và năm học.
     */
    public function homeroomTeachers(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $teachers = Teacher::with(['homeroomClasses' => fn ($query) => $query
            ->with(['academicYear', 'grade'])
            ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))])
            ->whereHas('homeroomClasses', fn ($query) => $query
                ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId)))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('teacher_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhereHas('homeroomClasses', fn ($class) => $class
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

    /**
     * Liệt kê giáo viên dạy theo môn và lọc theo năm học.
     */
    public function subjectTeachers(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $teachers = Teacher::with(['assignments' => fn ($query) => $query
            ->with(['academicYear', 'semester', 'class.grade', 'subject'])
            ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))])
            ->whereHas('assignments', fn ($query) => $query
                ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId)))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('teacher_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhereHas('assignments.subject', fn ($subject) => $subject
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

    /**
     * Tạo hồ sơ giáo viên mới và cấp tài khoản đăng nhập mặc định.
     */
    public function storeTeacher(StoreTeacherRequest $request, TeacherService $teacherService)
    {
        $teacher = $teacherService->create($request->validated());

        return to_route('admin.school.teachers.index')->with('success', "Đã tạo giáo viên và tài khoản {$teacher->email}. Mật khẩu mặc định: 123456. Giáo viên phải đổi mật khẩu khi đăng nhập lần đầu.");
    }

    /**
     * Hiển thị thông tin chi tiết giáo viên kèm lịch sử giảng dạy, chủ nhiệm và phân công.
     */
    public function showTeacher(Teacher $teacher, TeacherService $teacherService)
    {
        return view('admin.school.teachers.show', $teacherService->detailData($teacher));
    }

    /**
     * Mở form chỉnh sửa hồ sơ giáo viên.
     */
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

    /**
     * Hiển thị lịch sử công tác của giáo viên: môn dạy, chủ nhiệm, lịch, coi thi và năm học liên quan.
     */
    public function teacherHistory(Teacher $teacher)
    {
        $assignments = $teacher->assignments()
            ->with(['academicYear', 'semester', 'class.grade', 'subject'])
            ->orderByDesc('academic_year_id')
            ->orderBy('class_id')
            ->get();
        $homeroomAssignments = Schema::hasTable('homeroom_assignments')
            ? $teacher->homeroomAssignments()->with(['academicYear', 'class.grade'])->orderByDesc('academic_year_id')->get()
            : collect();
        $homeroomClasses = $homeroomAssignments->isNotEmpty()
            ? $homeroomAssignments->pluck('class')->filter()->unique('id')->values()
            : $teacher->homeroomClasses()->with(['academicYear', 'grade'])->orderByDesc('academic_year_id')->get();
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
            ->sortByDesc(fn ($assignment) => $assignment->exam?->exam_date?->format('Y-m-d').' '.($assignment->exam?->starts_at ?? ''))
            ->values();
        $subjectYears = $teacher->subjects->groupBy(fn ($subject) => $subject->pivot->academic_year_id);
        $yearIds = collect()
            ->merge($assignments->pluck('academic_year_id'))
            ->merge($homeroomAssignments->pluck('academic_year_id'))
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
            'homeroomAssignments',
            'schedules',
            'subjectYears',
            'examInvigilations'
        ));
    }

    /**
     * Cập nhật hồ sơ giáo viên và đồng bộ vai trò, môn dạy và phân công liên quan.
     */
    public function updateTeacher(UpdateTeacherRequest $request, Teacher $teacher, TeacherService $teacherService)
    {
        $teacherService->update($teacher, $request->validated());

        return to_route('admin.school.teachers.index')->with('success', 'Đã cập nhật hồ sơ giáo viên.');
    }

    /**
     * Xóa hoặc ngưng hiệu lực giáo viên sau khi kiểm tra lịch sử công tác và trách nhiệm đang hoạt động.
     */
    public function destroyTeacher(Teacher $teacher, TeacherService $teacherService)
    {
        $teacherService->delete($teacher);

        return back()->with('success', 'Đã xóa giáo viên.');
    }

    /**
     * Hiển thị danh sách khối lớp theo tên, cấp độ và bộ lọc tìm kiếm.
     */
    public function grades(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $level = $request->filled('level') ? (int) $request->input('level') : null;
        $grades = Grade::withCount('classes')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('level', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($level, fn ($query) => $query->where('level', $level))
            ->orderBy('level')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.grades.index', [
            'grades' => $grades,
            'levels' => Grade::orderBy('level')->pluck('level'),
        ]);
    }

    /**
     * Mở form tạo khối lớp mới.
     */
    public function createGrade()
    {
        return view('admin.school.grades.form', ['grade' => null]);
    }

    /**
     * Lưu khối lớp mới sau khi validate dữ liệu và ràng buộc mức học.
     */
    public function storeGrade(Request $request)
    {
        Grade::create($request->validate([
            'level' => ['required', 'integer', 'between:10,12', 'unique:grades,level'],
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]));

        return to_route('admin.school.grades.index')->with('success', 'Đã tạo khối lớp.');
    }

    /**
     * Mở form chỉnh sửa khối lớp.
     */
    public function editGrade(Grade $grade)
    {
        return view('admin.school.grades.form', compact('grade'));
    }

    /**
     * Cập nhật thông tin khối lớp và kiểm tra tính duy nhất của cấp độ.
     */
    public function updateGrade(Request $request, Grade $grade)
    {
        $grade->update($request->validate([
            'level' => ['required', 'integer', 'between:10,12', Rule::unique('grades', 'level')->ignore($grade)],
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]));

        return to_route('admin.school.grades.index')->with('success', 'Đã cập nhật khối lớp.');
    }

    /**
     * Xóa khối lớp khi chưa có lớp nào đang dùng.
     */
    public function destroyGrade(Grade $grade)
    {
        if ($grade->classes()->exists()) {
            return back()->with('error', 'Khối lớp đang có lớp sử dụng nên không thể xóa.');
        }
        $grade->delete();

        return back()->with('success', 'Đã xóa khối lớp.');
    }

    /**
     * Hiển thị danh sách môn học và lọc theo trạng thái hoạt động.
     */
    public function subjects(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $active = $request->input('active', '');
        $subjects = Subject::withCount(['assignments'])
            ->with(['assignments.teacher'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            }))
            ->when($active !== '', fn ($query) => $query->where('is_active', $active === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school.subjects.index', [
            'subjects' => $subjects,
            'subjectStatuses' => ['active' => 'Đang dùng', 'inactive' => 'Tạm ẩn'],
        ]);
    }

    /**
     * Mở form tạo môn học mới.
     */
    public function createSubject()
    {
        return view('admin.school.subjects.form', ['subject' => null]);
    }

    /**
     * Lưu thông tin môn học mới và áp dụng các quy tắc mã môn, số tiết và trạng thái hoạt động.
     */
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

    /**
     * Mở form chỉnh sửa môn học.
     */
    public function editSubject(Subject $subject)
    {
        return view('admin.school.subjects.form', compact('subject'));
    }

    /**
     * Cập nhật thông tin môn học với validation mã môn và cấu hình học tập.
     */
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

    /**
     * Xóa môn học khi chưa có phân công giảng dạy nào liên quan.
     */
    public function destroySubject(Subject $subject)
    {
        if ($subject->assignments()->exists()) {
            return back()->with('error', 'Môn học đang có phân công nên không thể xóa.');
        }
        $subject->delete();

        return back()->with('success', 'Đã xóa môn học.');
    }

    /**
     * Hiển thị danh sách lớp học với các bộ lọc năm học, khối, trạng thái và tìm kiếm.
     */
    public function classes(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $gradeId = $request->filled('grade_id') ? (int) $request->input('grade_id') : null;
        $status = trim((string) $request->input('status', ''));
        $classes = SchoolClass::with(['academicYear', 'grade', 'homeroomTeacher'])
            ->withCount(['enrollments' => fn ($query) => $query->where('status', 'active')])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('grade', fn ($grade) => $grade->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('homeroomTeacher', fn ($teacher) => $teacher->where('full_name', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
            ->when($gradeId, fn ($query) => $query->where('grade_id', $gradeId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
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

    /**
     * Mở form tạo lớp học mới.
     */
    public function createClass()
    {
        return view('admin.school.classes.form', [
            'schoolClass' => null,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'teachers' => Teacher::with('subjects')->where('status', 'active')->orderBy('full_name')->get(),
        ]);
    }

    /**
     * Hiển thị danh sách học sinh đang học trong một lớp và cho phép lọc theo từ khóa.
     */
    public function classStudents(Request $request, SchoolClass $schoolClass)
    {
        $search = trim((string) $request->input('q', ''));
        $schoolClass->load(['academicYear', 'grade', 'homeroomTeacher']);
        $enrollments = $schoolClass->enrollments()
            ->with('student')
            ->where('status', 'active')
            ->when($search !== '', fn ($query) => $query->whereHas('student', function ($student) use ($search): void {
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

    /**
     * Tạo lớp học mới, đồng thời xử lý xác thực năm học và giáo viên chủ nhiệm.
     */
    public function storeClass(Request $request, HomeroomAssignmentService $homeroomService)
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
        $homeroomTeacherId = isset($data['homeroom_teacher_id']) ? (int) $data['homeroom_teacher_id'] : null;
        $data['homeroom_teacher_id'] = null;
        DB::transaction(function () use ($data, $homeroomTeacherId, $homeroomService): void {
            $class = SchoolClass::create($data);
            if ($homeroomTeacherId) {
                $homeroomService->assign($class, Teacher::findOrFail($homeroomTeacherId));
            }
        });

        return to_route('admin.school.classes.index')->with('success', 'Đã tạo lớp học.');
    }

    /**
     * Mở form chỉnh sửa thông tin lớp học và chủ nhiệm hiện tại.
     */
    public function editClass(SchoolClass $schoolClass)
    {
        return view('admin.school.classes.form', [
            'schoolClass' => $schoolClass,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'teachers' => Teacher::where(function ($query) use ($schoolClass): void {
                $query->where('status', 'active')
                    ->when($schoolClass->homeroom_teacher_id, fn ($query) => $query->orWhere('id', $schoolClass->homeroom_teacher_id));
            })->orderBy('full_name')->get(),
        ]);
    }

    /**
     * Cập nhật lớp học và thay đổi chủ nhiệm nếu có, đồng thời kiểm tra các ràng buộc nghiệp vụ.
     */
    public function updateClass(Request $request, SchoolClass $schoolClass, HomeroomAssignmentService $homeroomService)
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
        if (
            (int) $schoolClass->academic_year_id !== (int) $year->id
            && ($schoolClass->enrollments()->exists() || $schoolClass->assignments()->exists() || $schoolClass->schedules()->exists())
        ) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Không thể đổi năm học của lớp đã phát sinh dữ liệu.',
            ]);
        }
        $data['academic_year_id'] = $year->id;
        unset($data['academic_year_name']);
        $newTeacherId = isset($data['homeroom_teacher_id']) ? (int) $data['homeroom_teacher_id'] : null;
        unset($data['homeroom_teacher_id']);
        DB::transaction(function () use ($schoolClass, $data, $newTeacherId, $homeroomService): void {
            $oldTeacherId = $schoolClass->homeroom_teacher_id ? (int) $schoolClass->homeroom_teacher_id : null;
            $teacherChanged = $oldTeacherId !== $newTeacherId
                || (int) $schoolClass->academic_year_id !== (int) $data['academic_year_id'];
            if ($teacherChanged && $oldTeacherId) {
                $homeroomService->unassign($schoolClass, Teacher::findOrFail($oldTeacherId));
            }
            $schoolClass->update($data);
            if ($teacherChanged && $newTeacherId) {
                $homeroomService->assign($schoolClass, Teacher::findOrFail($newTeacherId));
            }
        });

        return to_route('admin.school.classes.index')->with('success', 'Đã cập nhật lớp học.');
    }

    /**
     * Xóa lớp học nếu chưa có dữ liệu lịch sử học vụ, phân công hoặc điểm danh.
     */
    public function destroyClass(SchoolClass $schoolClass)
    {
        $hasHistory = $schoolClass->enrollments()->exists()
            || $schoolClass->assignments()->exists()
            || $schoolClass->schedules()->exists()
            || Score::where('class_id', $schoolClass->id)->exists()
            || AttendanceRecord::where('class_id', $schoolClass->id)->exists()
            || FeeAssessment::where('class_id', $schoolClass->id)->exists();
        if ($hasHistory) {
            throw ValidationException::withMessages([
                'class' => 'Không thể xóa lớp đã phát sinh dữ liệu học vụ hoặc tài chính.',
            ]);
        }
        $schoolClass->delete();

        return back()->with('success', 'Đã xóa lớp học.');
    }

    /**
     * Hiển thị danh sách phân công giảng dạy theo năm học, học kỳ, lớp, môn và giáo viên.
     */
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
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('class', fn ($class) => $class->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn ($subject) => $subject->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('teacher', fn ($teacher) => $teacher->where('full_name', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($teacherId, fn ($query) => $query->where('teacher_id', $teacherId))
            ->latest()
            ->paginate(10)
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

    /**
     * Mở form chỉnh sửa phân công giảng dạy hiện tại.
     */
    public function editAssignment(TeachingAssignment $assignment)
    {
        return view('admin.school.assignments.form', [
            'assignment' => $assignment->load(['academicYear', 'semester', 'class', 'subject', 'teacher']),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'teachers' => Teacher::where(function ($query) use ($assignment): void {
                $query->where('status', 'active')->orWhere('id', $assignment->teacher_id);
            })->orderBy('full_name')->get(),
        ]);
    }

    /**
     * Tạo mới phân công giảng dạy và kiểm tra điều kiện theo môn, giáo viên và năm học.
     */
    public function storeAssignment(StoreTeachingAssignmentRequest $request, TeachingAssignmentService $assignmentService)
    {
        $assignmentService->create($request->validated());

        return back()->with('success', 'Đã tạo phân công giảng dạy.');
    }

    /**
     * Cập nhật phân công giảng dạy với validation chuyên môn và lịch dạy.
     */
    public function updateAssignment(UpdateTeachingAssignmentRequest $request, TeachingAssignment $assignment, TeachingAssignmentService $assignmentService)
    {
        $assignmentService->update($assignment, $request->validated());

        return to_route('admin.school.assignments.index')->with('success', 'Đã cập nhật phân công giảng dạy.');
    }

    /**
     * Hiển thị chi tiết phân công của giáo viên trong một môn học, bao gồm lớp và năm học liên quan.
     */
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

    /**
     * Xóa hoặc đóng phân công giảng dạy tùy theo liệu đã phát sinh dữ liệu lịch sử hay chưa.
     */
    public function destroyAssignment(TeachingAssignment $assignment, TeachingAssignmentService $assignmentService)
    {
        $deleted = $assignmentService->deleteOrDeactivate($assignment);

        return back()->with('success', $deleted
            ? 'Đã xóa phân công chưa phát sinh dữ liệu.'
            : 'Phân công đã có lịch sử nên được chuyển sang trạng thái kết thúc.');
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

    /**
     * Hiển thị bảng điểm theo bộ lọc tìm kiếm, năm học, học kỳ, lớp, môn và loại điểm.
     */
    public function scores(Request $request)
    {
        $filters = [
            'search' => trim($request->input('q', '')),
            'year_id' => $request->integer('academic_year_id'),
            'semester_id' => $request->integer('semester_id'),
            'class_id' => $request->integer('class_id'),
            'grade_id' => $request->integer('grade_id'),
            'subject_id' => $request->integer('subject_id'),
            'score_type' => trim($request->input('score_type', '')),
            'date_from' => $request->date('date_from'),
            'date_to' => $request->date('date_to'),
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
                        fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_code', 'like', "%{$search}%")
                    )
                        ->orWhereHas(
                            'class',
                            fn ($q) => $q->where('name', 'like', "%{$search}%")
                        )
                        ->orWhereHas(
                            'subject',
                            fn ($q) => $q->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                        );
                });
            })
            ->when($filters['year_id'], fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($filters['semester_id'], fn ($q, $id) => $q->where('semester_id', $id))
            ->when($filters['class_id'], fn ($q, $id) => $q->where('class_id', $id))
            ->when(
                $filters['grade_id'],
                fn ($q, $id) => $q->whereHas('class', fn ($q) => $q->where('grade_id', $filters['grade_id']))
            )
            ->when($filters['subject_id'], fn ($q, $id) => $q->where('subject_id', $id))
            ->when($filters['score_type'], fn ($q, $type) => $q->where('score_type', $type))
            ->when($filters['date_from'], fn ($q, $date) => $q->whereDate('assessed_on', '>=', $date))
            ->when($filters['date_to'], fn ($q, $date) => $q->whereDate('assessed_on', '<=', $date))
            ->latest('assessed_on')
            ->get();

        $scoreGroups = $scoreRows
            ->groupBy(fn ($score) => "{$score->academic_year_id}|{$score->semester_id}|{$score->student_id}|{$score->class_id}|{$score->subject_id}")
            ->map(function ($group) {
                $first = $group->first();

                return (object) [
                    'scores' => $group->sortByDesc('assessed_on')->values(),
                    'student' => $first->student,
                    'class' => $first->class,
                    'subject' => $first->subject,
                    'semester' => $first->semester,
                    'academicYear' => $first->academicYear,
                    'latestTimestamp' => $group->max(fn ($score) => $score->assessed_on?->timestamp ?? 0),
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
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.school.scores.index', [
            'scores' => $scores,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('academicYear')
                ->where('status', 'active')
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->get(),
            'grades' => Grade::orderBy('level')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'scoreTypes' => [
                'oral' => 'Miệng',
                'fifteen_minute' => '15 phút',
                'one_period' => '1 tiết',
                'midterm' => 'Giữa kỳ',
                'final' => 'Cuối kỳ',
            ],
        ]);
    }

    /**
     * Hiển thị dữ liệu điểm danh theo ngày, lớp, môn và trạng thái hiện diện của học sinh.
     */
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
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$search}%")->orWhere('student_code', 'like', "%{$search}%"))
                    ->orWhereHas('class', fn ($class) => $class->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn ($subject) => $subject->where('name', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when($gradeId, fn ($query) => $query->whereHas('class', fn ($class) => $class->where('grade_id', $gradeId)))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
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

    /**
     * Hiển thị thời khóa biểu theo năm học, học kỳ, tuần và lớp chọn lọc.
     */
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
            ->when($selectedYearId, fn ($query) => $query->where('academic_year_id', $selectedYearId))
            ->when($selectedClassId, fn ($query) => $query->whereKey($selectedClassId))
            ->orderBy('name');
        // The schedule view renders one grid per class and needs collection
        // operations such as groupBy/whereIn; keep its options as a collection.
        $classOptions = (clone $classQuery)->get();
        $classes = $classOptions;
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $teachers = Teacher::with('subjects')->where('status', 'active')->orderBy('full_name')->get();

        $schedules = Schedule::with(['class.grade', 'subject', 'teacher', 'semester'])
            ->when($selectedYearId, fn ($query) => $query->where('academic_year_id', $selectedYearId))
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
            ->whereDate('week_start', $selectedWeekStart)
            ->when($selectedClassId, fn ($query) => $query->where('class_id', $selectedClassId))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('class', fn ($class) => $class->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('subject', fn ($subject) => $subject->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('teacher', fn ($teacher) => $teacher->where('full_name', 'like', "%{$search}%"))
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

    /**
     * Thêm tiết học mới vào thời khóa biểu với các kiểm tra xung đột lớp và giáo viên.
     */
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

    /**
     * Cập nhật tiết học hiện có và giữ nguyên cấu trúc thời khóa biểu hợp lệ.
     */
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

    /**
     * Xóa một tiết học khỏi thời khóa biểu.
     */
    public function destroySchedule(Schedule $schedule)
    {
        $schedule->delete();

        return back()->with('success', 'Đã xóa tiết học khỏi thời khóa biểu.');
    }

    /**
     * Hiển thị danh sách lịch thi theo năm học, học kỳ, lớp, môn và thời gian.
     */
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
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('exam_type', 'like', "%{$search}%")
                    ->orWhere('room', 'like', "%{$search}%")
                    ->orWhereHas('subject', fn ($subject) => $subject->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('class', fn ($class) => $class->where('name', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($examType !== '', fn ($query) => $query->where('exam_type', $examType))
            ->when($dateFrom, fn ($query) => $query->whereDate('exam_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('exam_date', '<=', $dateTo))
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

    /**
     * Mở form tạo lịch thi mới.
     */
    public function createExam()
    {
        return view('admin.school.exams.form', $this->examFormData());
    }

    /**
     * Mở form chỉnh sửa lịch thi đã tồn tại.
     */
    public function editExam(Exam $exam)
    {
        return view('admin.school.exams.form', $this->examFormData($exam));
    }

    /**
     * Lưu lịch thi mới sau khi validate thông tin ngày, giờ, môn và lớp.
     */
    public function storeExam(Request $request)
    {
        Exam::create($this->examData($request));

        return to_route('admin.school.exams.index')->with('success', 'Đã thêm lịch thi.');
    }

    /**
     * Cập nhật lịch thi và giữ nguyên các ràng buộc thời gian, lớp và môn học.
     */
    public function updateExam(Request $request, Exam $exam)
    {
        $exam->update($this->examData($request));

        return to_route('admin.school.exams.index')->with('success', 'Đã cập nhật lịch thi.');
    }

    /**
     * Xóa lịch thi khỏi hệ thống.
     */
    public function destroyExam(Exam $exam)
    {
        $exam->delete();

        return back()->with('success', 'Đã xóa lịch thi.');
    }

    /**
     * Hiển thị danh sách phân công coi thi theo năm học, giáo viên và khoảng thời gian.
     */
    public function examInvigilations(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $teacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;
        $dateFrom = $request->date('date_from');
        $dateTo = $request->date('date_to');

        $invigilations = ExamInvigilation::with(['exam.academicYear', 'exam.semester', 'exam.subject', 'exam.class', 'teacher'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('teacher', fn ($teacher) => $teacher
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('teacher_code', 'like', "%{$search}%"))
                    ->orWhereHas('exam', fn ($exam) => $exam
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('room', 'like', "%{$search}%"));
            }))
            ->when($yearId, fn ($query) => $query->whereHas('exam', fn ($exam) => $exam->where('academic_year_id', $yearId)))
            ->when($teacherId, fn ($query) => $query->where('teacher_id', $teacherId))
            ->when($dateFrom, fn ($query) => $query->whereHas('exam', fn ($exam) => $exam->whereDate('exam_date', '>=', $dateFrom)))
            ->when($dateTo, fn ($query) => $query->whereHas('exam', fn ($exam) => $exam->whereDate('exam_date', '<=', $dateTo)))
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

    /**
     * Mở form tạo phân công coi thi, có thể preselect lịch thi từ query string.
     */
    public function createExamInvigilation(Request $request)
    {
        return view('admin.school.exam-invigilations.form', $this->examInvigilationFormData(
            null,
            $request->filled('exam_id') ? (int) $request->input('exam_id') : null
        ));
    }

    /**
     * Lưu phân công coi thi và gửi thông báo cho giáo viên được phân công.
     */
    public function storeExamInvigilation(Request $request)
    {
        $invigilation = ExamInvigilation::create($this->examInvigilationData($request));
        $this->notifyExamInvigilator($invigilation, 'assigned');

        return to_route('admin.school.exam-invigilations.index')->with('success', 'Đã phân công giáo viên coi thi.');
    }

    /**
     * Mở form chỉnh sửa phân công coi thi.
     */
    public function editExamInvigilation(ExamInvigilation $examInvigilation)
    {
        return view('admin.school.exam-invigilations.form', $this->examInvigilationFormData($examInvigilation));
    }

    /**
     * Cập nhật phân công coi thi và kiểm tra trùng lịch, vai trò và thời gian.
     */
    public function updateExamInvigilation(Request $request, ExamInvigilation $examInvigilation)
    {
        $examInvigilation->update($this->examInvigilationData($request, $examInvigilation));
        $this->notifyExamInvigilator($examInvigilation, 'updated');

        return to_route('admin.school.exam-invigilations.index')->with('success', 'Đã cập nhật phân công coi thi.');
    }

    /**
     * Xóa phân công coi thi khỏi lịch thi.
     */
    public function destroyExamInvigilation(ExamInvigilation $examInvigilation)
    {
        $examInvigilation->delete();

        return back()->with('success', 'Đã xóa phân công coi thi.');
    }

    /**
     * Gửi thông báo cho giáo viên khi được phân công hoặc cập nhật lịch coi thi.
     */
    private function notifyExamInvigilator(ExamInvigilation $invigilation, string $action): void
    {
        $invigilation->load(['exam.subject', 'exam.class', 'teacher.user']);
        $invigilation->teacher?->user?->notify(new ExamInvigilationAssigned($invigilation, $action));
    }

    /**
     * Hiển thị danh sách thông báo và lọc theo đối tượng, loại, trạng thái xuất bản.
     */
    public function announcements(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $audience = trim((string) $request->input('audience', ''));
        $type = trim((string) $request->input('type', ''));
        $published = $request->input('published', '');
        $announcements = Announcement::with('class')->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('audience', 'like', "%{$search}%");
        }))
            ->when($audience !== '', fn ($query) => $query->where('audience', $audience))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($published !== '', fn ($query) => $query->where('is_published', $published === 'published'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.school.announcements.index', [
            'announcements' => $announcements,
            'audiences' => ['all' => 'Toàn trường', 'students' => 'Học sinh', 'teachers' => 'Giáo viên'],
            'announcementTypes' => Announcement::query()->select('type')->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    /**
     * Mở form tạo thông báo mới.
     */
    public function createAnnouncement()
    {
        return view('admin.school.announcements.form', $this->announcementFormData());
    }

    /**
     * Mở form chỉnh sửa thông báo hiện có.
     */
    public function editAnnouncement(Announcement $announcement)
    {
        return view('admin.school.announcements.form', $this->announcementFormData($announcement));
    }

    /**
     * Lưu thông báo mới với dữ liệu đối tượng và trạng thái xuất bản.
     */
    public function storeAnnouncement(Request $request)
    {
        Announcement::create($this->announcementData($request) + ['author_id' => auth()->id()]);

        return to_route('admin.school.announcements.index')->with('success', 'Đã tạo thông báo.');
    }

    /**
     * Cập nhật thông báo và giữ nguyên quyền xuất bản và đối tượng nhận.
     */
    public function updateAnnouncement(Request $request, Announcement $announcement)
    {
        $announcement->update($this->announcementData($request));

        return to_route('admin.school.announcements.index')->with('success', 'Đã cập nhật thông báo.');
    }

    /**
     * Xóa thông báo khỏi hệ thống.
     */
    public function destroyAnnouncement(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', 'Đã xóa thông báo.');
    }

    /**
     * Hiển thị danh sách khoản thu theo năm học, học kỳ, lớp và trạng thái kích hoạt.
     */
    public function fees(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $yearId = $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $semesterId = $request->filled('semester_id') ? (int) $request->input('semester_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $active = $request->input('active', '');
        $fees = FeeNotice::with(['payments'])
            ->withCount('payments')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when($active !== '', fn ($query) => $query->where('is_active', $active === 'active'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.school.fees.index', [
            'fees' => $fees,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ]);
    }

    /**
     * Mở form tạo khoản thu học phí mới, tránh mở lại chế độ legacy nếu hệ thống mới đã có fee assessments.
     */
    public function createFee()
    {
        if (Schema::hasTable('fee_assessments')) {
            return to_route('admin.finance.fees.index')
                ->with('warning', 'Khoản thu legacy chỉ còn chế độ tra cứu.');
        }

        return view('admin.school.fees.form', $this->feeFormData());
    }

    /**
     * Mở form chỉnh sửa khoản thu học phí, chặn sửa notice legacy nếu đang vận hành mô hình mới.
     */
    public function editFee(FeeNotice $fee)
    {
        if (Schema::hasTable('fee_assessments') && $fee->isLegacy()) {
            return to_route('admin.school.fees.index')
                ->with('warning', 'Không thể sửa notice legacy.');
        }

        return view('admin.school.fees.form', $this->feeFormData($fee));
    }

    /**
     * Lưu khoản thu học phí mới trên hệ thống legacy nếu chưa có mô hình fee assessment mới.
     */
    public function storeFee(Request $request)
    {
        if (Schema::hasTable('fee_assessments')) {
            throw ValidationException::withMessages([
                'fee' => 'Hãy dùng chức năng phát sinh học phí mới.',
            ]);
        }

        FeeNotice::create($this->feeData($request));

        return to_route('admin.school.fees.index')->with('success', 'Đã tạo khoản thu học phí.');
    }

    /**
     * Cập nhật khoản thu học phí, đồng thời chặn thao tác trên dữ liệu legacy không được phép sửa.
     */
    public function updateFee(Request $request, FeeNotice $fee)
    {
        if ($fee->isLegacy()) {
            throw ValidationException::withMessages(['fee' => 'Không thể sửa notice legacy.']);
        }

        $fee->update($this->feeData($request));

        return to_route('admin.school.fees.index')->with('success', 'Đã cập nhật khoản thu học phí.');
    }

    /**
     * Xóa khoản thu học phí khi chưa có dữ liệu tài chính hoặc lịch sử thanh toán liên quan.
     */
    public function destroyFee(FeeNotice $fee)
    {
        if ($fee->isLegacy() || $fee->assessments()->exists() || $fee->payments()->exists()) {
            throw ValidationException::withMessages([
                'fee' => 'Không thể xóa khoản thu đã có lịch sử tài chính.',
            ]);
        }

        $fee->delete();

        return back()->with('success', 'Đã xóa khoản thu học phí.');
    }

    /**
     * Chuẩn bị dữ liệu cho form lịch thi, bao gồm các danh sách hỗ trợ như năm học, học kỳ, môn và lớp.
     */
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

    /**
     * Validate dữ liệu đầu vào khi tạo hoặc cập nhật lịch thi.
     */
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

    /**
     * Chuẩn bị dữ liệu form phân công coi thi, bao gồm đề thi được chọn và danh sách giáo viên.
     */
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
                    ->when($examInvigilation, fn ($query) => $query->orWhere('id', $examInvigilation->teacher_id));
            })->orderBy('full_name')->get(),
            'roles' => $this->invigilationRoles(),
        ];
    }

    /**
     * Validate phân công coi thi và kiểm tra trùng lịch hoặc vai trò không hợp lệ.
     */
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
            ->when($examInvigilation, fn ($query) => $query->whereKeyNot($examInvigilation->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['teacher_id' => 'Giáo viên đã được phân công trong kỳ thi này.']);
        }

        $exam = Exam::findOrFail($data['exam_id']);
        $conflict = ExamInvigilation::where('teacher_id', $data['teacher_id'])
            ->when($examInvigilation, fn ($query) => $query->whereKeyNot($examInvigilation->id))
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

    /**
     * Trả về danh sách vai trò coi thi dùng trong hệ thống.
     */
    private function invigilationRoles(): array
    {
        return ['chief' => 'Cán bộ coi thi 1', 'invigilator' => 'Cán bộ coi thi 2', 'reserve' => 'Giáo viên dự phòng'];
    }

    /**
     * Validate dữ liệu thông báo trước khi lưu hoặc cập nhật.
     */
    private function announcementData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'string', 'max:30'],
            'audience' => ['required', Rule::in(['all', 'students', 'teachers'])],
            'class_id' => ['nullable', 'exists:classes,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]) + ['is_published' => $request->boolean('is_published')];
    }

    /**
     * Chuẩn bị dữ liệu form thông báo với lựa chọn lớp nhận thông báo.
     */
    private function announcementFormData(?Announcement $announcement = null): array
    {
        return [
            'announcement' => $announcement,
            'classes' => SchoolClass::with('academicYear', 'grade')
                ->where('status', 'active')
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Chuẩn bị dữ liệu form khoản thu học phí và các danh mục phụ trợ.
     */
    private function feeFormData(?FeeNotice $fee = null): array
    {
        return [
            'fee' => $fee,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('grade', 'academicYear')->where('status', 'active')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ];
    }

    /**
     * Validate dữ liệu khoản thu học phí trước khi lưu vào hệ thống.
     */
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

    /**
     * Validate dữ liệu tiết học và kiểm tra xung đột lớp, môn, giáo viên và học kỳ.
     */
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
            ->when($schedule, fn ($query) => $query->whereKeyNot($schedule->id))
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
                ->when($schedule, fn ($query) => $query->whereKeyNot($schedule->id))
                ->exists();
            if ($teacherConflict) {
                throw ValidationException::withMessages(['teacher_id' => 'Giáo viên đã có lịch dạy ở lớp khác trong tiết này.']);
            }
        }

        return $data;
    }

    /**
     * Trả về năm học hiện tại hoặc gần nhất nếu chưa có năm học đang hoạt động.
     */
    private function currentYear(): ?AcademicYear
    {
        return AcademicYear::where('is_current', true)->with('semesters')->first()
            ?? AcademicYear::latest('starts_on')->with('semesters')->first();
    }

    /**
     * Kiểm tra giáo viên chủ nhiệm có hợp lệ và chưa được phân công cho lớp khác trong cùng năm học.
     */
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
            ->when($classId, fn ($query) => $query->whereKeyNot($classId))
            ->exists();
        if ($alreadyAssigned) {
            throw ValidationException::withMessages(['homeroom_teacher_id' => 'Giáo viên này đã là chủ nhiệm của một lớp khác trong năm học đã chọn.']);
        }
    }

    /**
     * Tạo hoặc lấy đối tượng năm học theo ID hoặc định dạng tên năm học dạng YYYY-YYYY.
     */
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
            ['code' => $start.'-'.$end],
            ['name' => $start.'-'.$end, 'starts_on' => $start.'-08-15', 'ends_on' => $end.'-05-31', 'is_current' => false]
        );
        Semester::firstOrCreate(
            ['academic_year_id' => $year->id, 'number' => 1],
            ['name' => 'Semester 1', 'starts_on' => $start.'-08-15', 'ends_on' => $start.'-12-31', 'is_current' => false]
        );
        Semester::firstOrCreate(
            ['academic_year_id' => $year->id, 'number' => 2],
            ['name' => 'Semester 2', 'starts_on' => $end.'-01-01', 'ends_on' => $end.'-05-31', 'is_current' => false]
        );

        return $year;
    }

    /**
     * Validate dữ liệu học sinh và tách thông tin liên quan đến avatar, phụ huynh và đăng ký lớp.
     */
    private function studentData(Request $request, ?Student $student = null): array
    {
        $emailRules = ['email', 'max:255'];
        $emailRules[] = $student ? 'nullable' : 'required';
        if (! $student) {
            $emailRules[] = 'unique:users,email';
        }

        $data = $request->validate([
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
            'guardians' => ['nullable', 'array'],
            'guardians.*.full_name' => ['nullable', 'string', 'max:255'],
            'guardians.*.date_of_birth' => ['nullable', 'date'],
            'guardians.*.phone' => ['nullable', 'string', 'max:30'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],
            'guardians.*.occupation' => ['nullable', 'string', 'max:255'],
            'guardians.*.religion' => ['nullable', 'string', 'max:100'],
            'guardians.*.workplace' => ['nullable', 'string', 'max:255'],
            'guardians.*.address' => ['nullable', 'string', 'max:255'],
            'guardians.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] !== 'studying') {
            $data['academic_year_id'] = null;
            $data['class_id'] = null;
        }

        $enrollment = ['academic_year_id' => $data['academic_year_id'] ?? null, 'class_id' => $data['class_id'] ?? null];
        $avatar = $data['avatar'] ?? null;
        $guardians = $data['guardians'] ?? [];
        unset($data['avatar']);
        unset($data['academic_year_id'], $data['class_id'], $data['guardians']);

        return ['student' => $data, 'avatar' => $avatar, 'guardians' => $guardians, ...$enrollment];
    }

    /**
     * Đồng bộ trạng thái nhập học và lớp đang học của học sinh theo năm học hiện tại.
     */
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

    /**
     * Validate dữ liệu giáo viên trước khi lưu hoặc cập nhật hồ sơ.
     */
    private function teacherData(Request $request, ?Teacher $teacher = null): array
    {
        $emailRules = ['email', 'max:255'];
        $emailRules[] = $teacher ? 'nullable' : 'required';
        if (! $teacher) {
            $emailRules[] = 'unique:users,email';
        }

        $data = $request->validate([
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

    /**
     * Lưu ảnh đại diện mới cho giáo viên và xóa ảnh cũ nếu có.
     */
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

    /**
     * Đồng bộ môn giảng dạy và chủ nhiệm của giáo viên theo năm học đã chọn.
     */
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
        $subjectIds = collect($data['subjects'] ?? [])->map(fn ($id) => (int) $id)->values();
        $homeroomClassIds = collect($data['homeroom_class_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        $existingHomeroomIds = $teacher->homeroomClasses()
            ->where('academic_year_id', $yearId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
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
                $subjectIds->map(fn (int $subjectId) => [
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

    /**
     * Tắt các nhiệm vụ đang hoạt động của giáo viên khi ngừng công tác hoặc xóa hồ sơ.
     */
    private function deactivateTeacherResponsibilities(Teacher $teacher): void
    {
        $teacher->assignments()->where('status', 'active')->update(['status' => 'inactive']);
        $teacher->homeroomClasses()->update(['homeroom_teacher_id' => null]);
        $teacher->schedules()
            ->whereDate('week_start', '>=', today()->startOfWeek())
            ->update(['teacher_id' => null]);
        $teacher->examInvigilations()
            ->whereHas('exam', fn ($query) => $query->whereDate('exam_date', '>=', today()))
            ->delete();
    }

    /**
     * Đồng bộ danh sách phụ huynh cho học sinh, bao gồm thông tin liên hệ và mối quan hệ chính.
     */
    private function syncGuardians(Student $student, array $rows): void
    {
        $relationships = ['father', 'mother'];
        $existing = $student->guardians()->get()->keyBy('pivot.relationship');
        $primaryRelationship = filled(data_get($rows, 'father.full_name')) ? 'father' : 'mother';
        $fields = [
            'full_name',
            'date_of_birth',
            'phone',
            'email',
            'occupation',
            'religion',
            'workplace',
            'address',
            'notes',
        ];

        foreach ($relationships as $relationship) {
            $payload = collect($rows[$relationship] ?? [])
                ->only($fields)
                ->map(fn ($value) => filled($value) ? $value : null)
                ->all();
            $guardian = $existing->get($relationship);
            $hasData = collect($payload)->contains(fn ($value) => filled($value));

            if (! $hasData) {
                if ($guardian) {
                    $student->guardians()->detach($guardian->id);
                    if ($guardian->students()->doesntExist()) {
                        $guardian->delete();
                    }
                }

                continue;
            }

            if (blank($payload['full_name'] ?? null)) {
                throw ValidationException::withMessages([
                    "guardians.{$relationship}.full_name" => 'Vui lòng nhập họ tên phụ huynh khi có thông tin liên hệ.',
                ]);
            }

            $guardian ??= new Guardian;
            $guardian->fill($payload)->save();
            $student->guardians()->syncWithoutDetaching([
                $guardian->id => [
                    'relationship' => $relationship,
                    'is_primary' => $relationship === $primaryRelationship,
                ],
            ]);
            $student->guardians()->updateExistingPivot($guardian->id, [
                'relationship' => $relationship,
                'is_primary' => $relationship === $primaryRelationship,
            ]);
        }
    }

    /**
     * Tạo tài khoản người dùng cho học sinh với mật khẩu mặc định và vai trò học sinh.
     */
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

    /**
     * Tạo tài khoản người dùng cho giáo viên và gán vai trò theo chức năng chủ nhiệm, giảng dạy và môn phụ trách.
     */
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
