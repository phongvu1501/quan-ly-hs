<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Exam;
use App\Models\FeeNotice;
use App\Models\FeePayment;
use App\Models\Score;
use App\Models\Schedule;
use App\Models\Student;

class StudentPortalController extends Controller
{
    public function dashboard()
    {
        $student = auth()->user()->studentProfile;
        abort_unless($student, 403, 'This account is not linked to a student profile.');
        $student->load('guardians');
        $year = AcademicYear::where('is_current', true)->first() ?: AcademicYear::latest('starts_on')->first();
        $enrollment = $student->enrollments()->with(['class.grade', 'academicYear'])
            ->where('academic_year_id', $year?->id)
            ->where('status', 'active')
            ->latest()
            ->first();
        $semester = $year?->semesters()->where('is_current', true)->first() ?: $year?->semesters()->orderBy('number')->first();

        $scores = Score::with(['subject', 'semester'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $year?->id)
            ->latest('assessed_on')->get();
        $scoreGroups = $scores->groupBy(fn ($score) => $score->subject_id);
        $attendance = $student->attendance()->where('academic_year_id', $year?->id)->get()->groupBy('status');
        $attendanceRecords = $student->attendance()
            ->with(['subject', 'class'])
            ->where('academic_year_id', $year?->id)
            ->latest('attended_on')
            ->latest()
            ->limit(12)
            ->get();
        $comments = $student->comments()
            ->with(['subject', 'teacher'])
            ->where('academic_year_id', $year?->id)
            ->when($semester, fn ($query) => $query->where('semester_id', $semester->id))
            ->latest()
            ->limit(8)
            ->get();
        $classId = $enrollment?->class_id;

        return view('student.dashboard', [
            'student' => $student,
            'year' => $year,
            'semester' => $semester,
            'enrollment' => $enrollment,
            'scores' => $scores,
            'scoreGroups' => $scoreGroups,
            'attendance' => $attendance,
            'attendanceRecords' => $attendanceRecords,
            'comments' => $comments,
            'schedule' => $classId && $semester ? Schedule::with(['subject', 'teacher'])->where('class_id', $classId)->where('semester_id', $semester->id)->orderBy('weekday')->orderBy('period')->get() : collect(),
            'exams' => Exam::with('subject')->where('semester_id', $semester?->id)->where(fn ($query) => $query->whereNull('class_id')->orWhere('class_id', $classId))->whereDate('exam_date', '>=', today())->orderBy('exam_date')->get(),
            'announcements' => Announcement::where('is_published', true)->where(function ($query) use ($classId): void {
                $query->where('audience', 'all')
                    ->orWhere(function ($query) use ($classId): void {
                        $query->where('audience', 'students')
                            ->where(function ($query) use ($classId): void {
                                $query->whereNull('class_id');
                                if ($classId) {
                                    $query->orWhere('class_id', $classId);
                                }
                            });
                    });
            })->where(function ($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))->latest('published_at')->limit(8)->get(),
            'fees' => $this->feeNotices($student, $year?->id, $classId),
        ]);
    }

    public function payments()
    {
        $student = auth()->user()->studentProfile;
        abort_unless($student, 403, 'This account is not linked to a student profile.');
        $year = AcademicYear::where('is_current', true)->first() ?: AcademicYear::latest('starts_on')->first();
        $enrollment = $student->enrollments()
            ->where('academic_year_id', $year?->id)
            ->where('status', 'active')
            ->latest()
            ->first();
        $payments = $student->payments()->with(['feeNotice.academicYear', 'feeNotice.semester', 'feeNotice.class'])->latest('paid_at')->latest()->get();
        $notices = $this->feeNotices($student, $year?->id, $enrollment?->class_id);

        return view('student.payments', compact('student', 'year', 'payments', 'notices'));
    }

    private function feeNotices(Student $student, ?int $yearId, ?int $classId)
    {
        if (! $yearId) {
            return collect();
        }

        return FeeNotice::where('academic_year_id', $yearId)
            ->where('is_active', true)
            ->where(function ($query) use ($classId): void {
                $query->whereNull('class_id');
                if ($classId) {
                    $query->orWhere('class_id', $classId);
                }
            })
            ->with(['payments' => fn ($query) => $query->where('student_id', $student->id)])
            ->latest()
            ->get();
    }
}
