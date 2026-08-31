<?php

use App\Http\Controllers\Admin\Finance\FeeAssessmentController;
use App\Http\Controllers\Admin\Finance\FeeCategoryController;
use App\Http\Controllers\Admin\Finance\FeePaymentController;
use App\Http\Controllers\Admin\Finance\FeePolicyController;
use App\Http\Controllers\Admin\Finance\FeeRateController;
use App\Http\Controllers\Admin\Finance\FinanceDashboardController;
use App\Http\Controllers\Admin\Finance\FinanceReportController;
use App\Http\Controllers\Admin\Finance\InsuranceConfigController;
use App\Http\Controllers\Admin\Finance\PayrollController;
use App\Http\Controllers\Admin\Finance\PayrollInputController;
use App\Http\Controllers\Admin\Finance\PayrollPaymentController;
use App\Http\Controllers\Admin\Finance\SalaryComponentController;
use App\Http\Controllers\Admin\Finance\SalaryProfileController;
use App\Http\Controllers\Admin\Finance\StudentFeePolicyController;
use App\Http\Controllers\Admin\Finance\TaxConfigController;
use App\Http\Controllers\Admin\Finance\TeachingExtraController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DynamicMenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\TeacherPortalController;
use App\Http\Middleware\EnsureFinanceSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
| Guest Routes
*/

Route::middleware('guest')->group(function () {

    // Register
    // Route::get('/register', [AuthController::class, 'showRegister'])
    //     ->name('register');

    // Route::post('/register', [AuthController::class, 'register']);

    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login']);
});

/*
| Home
*/

Route::get('/', function () {

    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

/*
| Logout
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
| Dashboard
*/

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {

        $user = auth()->user();

        $target = app(AuthController::class)
            ->dashboardUrl($user);

        return $target === route('dashboard')
            ? view('admin.layouts.app')
            : redirect()->to($target);
    })->name('dashboard');
});

/*
| Profile
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile.show');

    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    /*

    | Teacher Portal

    */

    Route::get('/teacher/dashboard', [TeacherPortalController::class, 'dashboard'])
        ->name('teacher.dashboard')
        ->middleware('permission:teacher.dashboard');

    Route::get('/teacher/homeroom', [TeacherPortalController::class, 'homeroomDashboard'])
        ->name('teacher.homeroom.dashboard')
        ->middleware('permission:homeroom.dashboard');

    Route::get('/teacher/subject', [TeacherPortalController::class, 'subjectDashboard'])
        ->name('teacher.subject.dashboard')
        ->middleware('permission:teacher.dashboard');

    /*

    | Teacher - Scores

    */

    Route::get('/teacher/scores', [TeacherPortalController::class, 'scores'])
        ->name('teacher.scores')
        ->middleware('permission:scores.view');

    Route::post('/teacher/scores', [TeacherPortalController::class, 'storeScore'])
        ->name('teacher.scores.store')
        ->middleware('permission:scores.create');

    Route::put('/teacher/scores/{score}', [TeacherPortalController::class, 'updateScore'])
        ->name('teacher.scores.update')
        ->middleware('permission:scores.edit');

    /*

    | Teacher - Attendance

    */

    Route::get('/teacher/attendance', [TeacherPortalController::class, 'attendance'])
        ->name('teacher.attendance')
        ->middleware('permission:attendance.view');

    Route::post('/teacher/attendance', [TeacherPortalController::class, 'storeAttendance'])
        ->name('teacher.attendance.store')
        ->middleware('permission:attendance.create');

    Route::put('/teacher/attendance/{attendance}', [TeacherPortalController::class, 'updateAttendance'])
        ->name('teacher.attendance.update')
        ->middleware('permission:attendance.edit');

    /*

    | Teacher - Comments

    */

    Route::get('/teacher/comments', [TeacherPortalController::class, 'comments'])
        ->name('teacher.comments')
        ->middleware('permission:scores.view');

    Route::post('/teacher/comments', [TeacherPortalController::class, 'storeComment'])
        ->name('teacher.comments.store')
        ->middleware('permission:scores.create');

    Route::put('/teacher/comments/{comment}', [TeacherPortalController::class, 'updateComment'])
        ->name('teacher.comments.update')
        ->middleware('permission:scores.edit');

    /*
    | Student Portal
    */

    Route::get('/student/dashboard', [StudentPortalController::class, 'dashboard'])
        ->name('student.dashboard')
        ->middleware('permission:student.dashboard');

    Route::get('/student/payments', [StudentPortalController::class, 'payments'])
        ->name('student.payments')
        ->middleware('permission:student-fees.view');

    /*
    | Teacher Notifications
    */

    Route::post('/teacher/notifications/{notification}/read', [TeacherPortalController::class, 'markNotificationRead'])
        ->name('teacher.notifications.read')
        ->middleware('permission:teacher.dashboard');
});

/*
| Admin Routes
*/

Route::middleware(['auth', 'backoffice'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        | Admin Dashboard
        */

        Route::get('/', function () {
            return redirect()->route('dashboard');
        })
            ->middleware('permission:admin.access')
            ->name('dashboard');

        /*
        | ROLES
        */

        Route::get('/roles', [RoleController::class, 'index'])
            ->name('roles.index')
            ->middleware('permission:roles.view');

        Route::get('/roles/create', [RoleController::class, 'create'])
            ->name('roles.create')
            ->middleware('permission:roles.create');

        Route::post('/roles', [RoleController::class, 'store'])
            ->name('roles.store')
            ->middleware('permission:roles.create');

        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->name('roles.edit')
            ->middleware('permission:roles.edit');

        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->name('roles.update')
            ->middleware('permission:roles.edit');

        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->name('roles.destroy')
            ->middleware('permission:roles.delete');

        /*
        | USERS
        */

        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index')
            ->middleware('permission:users.view');

        Route::get('/users/create', [UserController::class, 'create'])
            ->name('users.create')
            ->middleware('permission:users.create');

        Route::post('/users', [UserController::class, 'store'])
            ->name('users.store')
            ->middleware('permission:users.create');

        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit')
            ->middleware('permission:users.edit');

        Route::put('/users/{user}', [UserController::class, 'update'])
            ->name('users.update')
            ->middleware('permission:users.edit');

        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy')
            ->middleware('permission:users.delete');

        /*
        | PERMISSIONS
        */

        Route::get('/permissions', [PermissionController::class, 'index'])
            ->name('permissions.index')
            ->middleware('permission:permissions.view');

        Route::get('/permissions/create', [PermissionController::class, 'create'])
            ->name('permissions.create')
            ->middleware('permission:permissions.create');

        Route::post('/permissions', [PermissionController::class, 'store'])
            ->name('permissions.store')
            ->middleware('permission:permissions.create');

        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])
            ->name('permissions.edit')
            ->middleware('permission:permissions.edit');

        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])
            ->name('permissions.update')
            ->middleware('permission:permissions.edit');

        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])
            ->name('permissions.destroy')
            ->middleware('permission:permissions.delete');

        /*
        | MENUS
        */

        Route::get('/menus', [MenuController::class, 'index'])
            ->name('menus.index')
            ->middleware('permission:menus.view');

        Route::get('/menus/create', [MenuController::class, 'create'])
            ->name('menus.create')
            ->middleware('permission:menus.create');

        Route::post('/menus', [MenuController::class, 'store'])
            ->name('menus.store')
            ->middleware('permission:menus.create');

        Route::get('/menus/{menu}/edit', [MenuController::class, 'edit'])
            ->name('menus.edit')
            ->middleware('permission:menus.edit');

        Route::put('/menus/{menu}', [MenuController::class, 'update'])
            ->name('menus.update')
            ->middleware('permission:menus.edit');

        Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])
            ->name('menus.destroy')
            ->middleware('permission:menus.delete');
    });

/*
| SCHOOL MANAGEMENT
*/

Route::middleware(['auth', 'backoffice'])
    ->prefix('admin/school')
    ->name('admin.school.')
    ->group(function () {

        /*
        | School Dashboard
        */

        Route::get('/', [SchoolController::class, 'dashboard'])
            ->name('dashboard')
            ->middleware('permission:school.dashboard');

        /*
        | STUDENTS
        */

        Route::get('/students', [SchoolController::class, 'students'])
            ->name('students.index')
            ->middleware('permission:students.view');

        Route::get('/students/create', [SchoolController::class, 'createStudent'])
            ->name('students.create')
            ->middleware('permission:students.create');

        Route::post('/students', [SchoolController::class, 'storeStudent'])
            ->name('students.store')
            ->middleware('permission:students.create');

        Route::get('/students/{student}/payments', [SchoolController::class, 'studentPayments'])
            ->name('students.payments')
            ->middleware('permission:fees.view');

        Route::post('/students/{student}/payments', [SchoolController::class, 'storeStudentPayment'])
            ->name('students.payments.store')
            ->middleware('permission:fees.create');

        Route::get('/students/{student}/transcript', [SchoolController::class, 'studentTranscript'])
            ->name('students.transcript')
            ->middleware('permission:students.view');

        Route::get('/students/{student}', [SchoolController::class, 'showStudent'])
            ->name('students.show')
            ->middleware('permission:students.view');

        Route::get('/students/{student}/edit', [SchoolController::class, 'editStudent'])
            ->name('students.edit')
            ->middleware('permission:students.edit');

        Route::put('/students/{student}', [SchoolController::class, 'updateStudent'])
            ->name('students.update')
            ->middleware('permission:students.edit');

        Route::delete('/students/{student}', [SchoolController::class, 'destroyStudent'])
            ->name('students.destroy')
            ->middleware('permission:students.delete');

        /*
        | TEACHERS
        */

        Route::get('/teachers', [SchoolController::class, 'teachers'])
            ->name('teachers.index')
            ->middleware('permission:teachers.view');

        Route::get('/teachers/homeroom', [SchoolController::class, 'homeroomTeachers'])
            ->name('teachers.homeroom')
            ->middleware('permission:teachers.view');

        Route::get('/teachers/subject', [SchoolController::class, 'subjectTeachers'])
            ->name('teachers.subject')
            ->middleware('permission:teachers.view');

        Route::get('/teachers/create', [SchoolController::class, 'createTeacher'])
            ->name('teachers.create')
            ->middleware('permission:teachers.create');

        Route::post('/teachers', [SchoolController::class, 'storeTeacher'])
            ->name('teachers.store')
            ->middleware('permission:teachers.create');

        Route::get('/teachers/{teacher}/edit', [SchoolController::class, 'editTeacher'])
            ->name('teachers.edit')
            ->middleware('permission:teachers.edit');

        Route::put('/teachers/{teacher}', [SchoolController::class, 'updateTeacher'])
            ->name('teachers.update')
            ->middleware('permission:teachers.edit');

        Route::get('/teachers/{teacher}/history', [SchoolController::class, 'teacherHistory'])
            ->name('teachers.history')
            ->middleware('permission:teachers.view');

        Route::get('/teachers/{teacher}', [SchoolController::class, 'showTeacher'])
            ->name('teachers.show')
            ->middleware('permission:teachers.view');

        Route::delete('/teachers/{teacher}', [SchoolController::class, 'destroyTeacher'])
            ->name('teachers.destroy')
            ->middleware('permission:teachers.delete');

        /*
        | GRADES
        */

        Route::get('/grades', [SchoolController::class, 'grades'])
            ->name('grades.index')
            ->middleware('permission:grades.view');

        Route::get('/grades/create', [SchoolController::class, 'createGrade'])
            ->name('grades.create')
            ->middleware('permission:grades.create');

        Route::post('/grades', [SchoolController::class, 'storeGrade'])
            ->name('grades.store')
            ->middleware('permission:grades.create');

        Route::get('/grades/{grade}/edit', [SchoolController::class, 'editGrade'])
            ->name('grades.edit')
            ->middleware('permission:grades.edit');

        Route::put('/grades/{grade}', [SchoolController::class, 'updateGrade'])
            ->name('grades.update')
            ->middleware('permission:grades.edit');

        Route::delete('/grades/{grade}', [SchoolController::class, 'destroyGrade'])
            ->name('grades.destroy')
            ->middleware('permission:grades.delete');

        /*
        | SUBJECTS
        */

        Route::get('/subjects', [SchoolController::class, 'subjects'])
            ->name('subjects.index')
            ->middleware('permission:subjects.view');

        Route::get('/subjects/create', [SchoolController::class, 'createSubject'])
            ->name('subjects.create')
            ->middleware('permission:subjects.create');

        Route::post('/subjects', [SchoolController::class, 'storeSubject'])
            ->name('subjects.store')
            ->middleware('permission:subjects.create');

        Route::get('/subjects/{subject}/edit', [SchoolController::class, 'editSubject'])
            ->name('subjects.edit')
            ->middleware('permission:subjects.edit');

        Route::get('/subjects/{subject}/teachers/{teacher}', [SchoolController::class, 'subjectTeacherDetail'])
            ->name('subjects.teachers.show')
            ->middleware('permission:subjects.view');

        Route::put('/subjects/{subject}', [SchoolController::class, 'updateSubject'])
            ->name('subjects.update')
            ->middleware('permission:subjects.edit');

        Route::delete('/subjects/{subject}', [SchoolController::class, 'destroySubject'])
            ->name('subjects.destroy')
            ->middleware('permission:subjects.delete');

        /*
        | CLASSES
        */

        Route::get('/classes', [SchoolController::class, 'classes'])
            ->name('classes.index')
            ->middleware('permission:classes.view');

        Route::get('/classes/create', [SchoolController::class, 'createClass'])
            ->name('classes.create')
            ->middleware('permission:classes.create');

        Route::post('/classes', [SchoolController::class, 'storeClass'])
            ->name('classes.store')
            ->middleware('permission:classes.create');

        Route::get('/classes/{schoolClass}/students', [SchoolController::class, 'classStudents'])
            ->name('classes.students')
            ->middleware('permission:classes.view');

        Route::get('/classes/{schoolClass}/edit', [SchoolController::class, 'editClass'])
            ->name('classes.edit')
            ->middleware('permission:classes.edit');

        Route::put('/classes/{schoolClass}', [SchoolController::class, 'updateClass'])
            ->name('classes.update')
            ->middleware('permission:classes.edit');

        Route::delete('/classes/{schoolClass}', [SchoolController::class, 'destroyClass'])
            ->name('classes.destroy')
            ->middleware('permission:classes.delete');

        /*
        | ASSIGNMENTS
        */

        Route::get('/assignments', [SchoolController::class, 'assignments'])
            ->name('assignments.index')
            ->middleware('permission:assignments.view');

        Route::get('/assignments/create', [SchoolController::class, 'assignments'])
            ->name('assignments.create')
            ->middleware('permission:assignments.create');

        Route::post('/assignments', [SchoolController::class, 'storeAssignment'])
            ->name('assignments.store')
            ->middleware('permission:assignments.create');

        Route::get('/assignments/{assignment}/edit', [SchoolController::class, 'editAssignment'])
            ->name('assignments.edit')
            ->middleware('permission:assignments.edit');

        Route::put('/assignments/{assignment}', [SchoolController::class, 'updateAssignment'])
            ->name('assignments.update')
            ->middleware('permission:assignments.edit');

        Route::delete('/assignments/{assignment}', [SchoolController::class, 'destroyAssignment'])
            ->name('assignments.destroy')
            ->middleware('permission:assignments.delete');

        /*
        | SCORES
        */

        Route::get('/scores', [SchoolController::class, 'scores'])
            ->name('scores.index')
            ->middleware('permission:scores.view');

        /*
        | ATTENDANCE
        */

        Route::get('/attendance', [SchoolController::class, 'attendance'])
            ->name('attendance.index')
            ->middleware('permission:attendance.view');

        /*
        | SCHEDULES
        */

        Route::get('/schedules', [SchoolController::class, 'schedules'])
            ->name('schedules.index')
            ->middleware('permission:schedules.view');

        Route::post('/schedules', [SchoolController::class, 'storeSchedule'])
            ->name('schedules.store')
            ->middleware('permission:schedules.create');

        Route::put('/schedules/{schedule}', [SchoolController::class, 'updateSchedule'])
            ->name('schedules.update')
            ->middleware('permission:schedules.edit');

        Route::delete('/schedules/{schedule}', [SchoolController::class, 'destroySchedule'])
            ->name('schedules.destroy')
            ->middleware('permission:schedules.delete');

        /*
        | EXAMS
        */

        Route::get('/exams', [SchoolController::class, 'exams'])
            ->name('exams.index')
            ->middleware('permission:exams.view');

        Route::get('/exams/create', [SchoolController::class, 'createExam'])
            ->name('exams.create')
            ->middleware('permission:exams.create');

        Route::post('/exams', [SchoolController::class, 'storeExam'])
            ->name('exams.store')
            ->middleware('permission:exams.create');

        Route::get('/exams/{exam}/edit', [SchoolController::class, 'editExam'])
            ->name('exams.edit')
            ->middleware('permission:exams.edit');

        Route::put('/exams/{exam}', [SchoolController::class, 'updateExam'])
            ->name('exams.update')
            ->middleware('permission:exams.edit');

        Route::delete('/exams/{exam}', [SchoolController::class, 'destroyExam'])
            ->name('exams.destroy')
            ->middleware('permission:exams.delete');

        /*
        | EXAM INVIGILATIONS
        */

        Route::get('/exam-invigilations', [SchoolController::class, 'examInvigilations'])
            ->name('exam-invigilations.index')
            ->middleware('permission:exam-invigilations.view');

        Route::get('/exam-invigilations/create', [SchoolController::class, 'createExamInvigilation'])
            ->name('exam-invigilations.create')
            ->middleware('permission:exam-invigilations.create');

        Route::post('/exam-invigilations', [SchoolController::class, 'storeExamInvigilation'])
            ->name('exam-invigilations.store')
            ->middleware('permission:exam-invigilations.create');

        Route::get('/exam-invigilations/{examInvigilation}/edit', [SchoolController::class, 'editExamInvigilation'])
            ->name('exam-invigilations.edit')
            ->middleware('permission:exam-invigilations.edit');

        Route::put('/exam-invigilations/{examInvigilation}', [SchoolController::class, 'updateExamInvigilation'])
            ->name('exam-invigilations.update')
            ->middleware('permission:exam-invigilations.edit');

        Route::delete('/exam-invigilations/{examInvigilation}', [SchoolController::class, 'destroyExamInvigilation'])
            ->name('exam-invigilations.destroy')
            ->middleware('permission:exam-invigilations.delete');

        /*
        | ANNOUNCEMENTS
        */

        Route::get('/announcements', [SchoolController::class, 'announcements'])
            ->name('announcements.index')
            ->middleware('permission:announcements.view');

        Route::get('/announcements/create', [SchoolController::class, 'createAnnouncement'])
            ->name('announcements.create')
            ->middleware('permission:announcements.create');

        Route::post('/announcements', [SchoolController::class, 'storeAnnouncement'])
            ->name('announcements.store')
            ->middleware('permission:announcements.create');

        Route::get('/announcements/{announcement}/edit', [SchoolController::class, 'editAnnouncement'])
            ->name('announcements.edit')
            ->middleware('permission:announcements.edit');

        Route::put('/announcements/{announcement}', [SchoolController::class, 'updateAnnouncement'])
            ->name('announcements.update')
            ->middleware('permission:announcements.edit');

        Route::delete('/announcements/{announcement}', [SchoolController::class, 'destroyAnnouncement'])
            ->name('announcements.destroy')
            ->middleware('permission:announcements.delete');

        /*
        | FEES
        */

        Route::get('/fees', [SchoolController::class, 'fees'])
            ->name('fees.index')
            ->middleware('permission:fees.view');

        Route::get('/fees/create', [SchoolController::class, 'createFee'])
            ->name('fees.create')
            ->middleware('permission:fees.create');

        Route::post('/fees', [SchoolController::class, 'storeFee'])
            ->name('fees.store')
            ->middleware('permission:fees.create');

        Route::get('/fees/{fee}/edit', [SchoolController::class, 'editFee'])
            ->name('fees.edit')
            ->middleware('permission:fees.edit');

        Route::put('/fees/{fee}', [SchoolController::class, 'updateFee'])
            ->name('fees.update')
            ->middleware('permission:fees.edit');

        Route::delete('/fees/{fee}', [SchoolController::class, 'destroyFee'])
            ->name('fees.destroy')
            ->middleware('permission:fees.delete');
    });

/*
| FINANCE MANAGEMENT
*/

Route::middleware(['auth', 'backoffice', EnsureFinanceSchema::class])
    ->prefix('admin/finance')
    ->name('admin.finance.')
    ->group(function () {
        Route::get('/', FinanceDashboardController::class)
            ->name('dashboard')->middleware('permission:finance.dashboard');

        Route::get('/fee-categories', [FeeCategoryController::class, 'index'])->name('fee-categories.index')->middleware('permission:fee-categories.view');
        Route::get('/fee-categories/create', [FeeCategoryController::class, 'create'])->name('fee-categories.create')->middleware('permission:fee-categories.create');
        Route::post('/fee-categories', [FeeCategoryController::class, 'store'])->name('fee-categories.store')->middleware('permission:fee-categories.create');
        Route::get('/fee-categories/{feeCategory}/edit', [FeeCategoryController::class, 'edit'])->name('fee-categories.edit')->middleware('permission:fee-categories.edit');
        Route::put('/fee-categories/{feeCategory}', [FeeCategoryController::class, 'update'])->name('fee-categories.update')->middleware('permission:fee-categories.edit');
        Route::delete('/fee-categories/{feeCategory}', [FeeCategoryController::class, 'destroy'])->name('fee-categories.destroy')->middleware('permission:fee-categories.delete');

        Route::get('/fee-rates', [FeeRateController::class, 'index'])->name('fee-rates.index')->middleware('permission:fee-rates.view');
        Route::get('/fee-rates/create', [FeeRateController::class, 'create'])->name('fee-rates.create')->middleware('permission:fee-rates.create');
        Route::post('/fee-rates', [FeeRateController::class, 'store'])->name('fee-rates.store')->middleware('permission:fee-rates.create');
        Route::get('/fee-rates/{feeRate}/edit', [FeeRateController::class, 'edit'])->name('fee-rates.edit')->middleware('permission:fee-rates.edit');
        Route::put('/fee-rates/{feeRate}', [FeeRateController::class, 'update'])->name('fee-rates.update')->middleware('permission:fee-rates.edit');
        Route::delete('/fee-rates/{feeRate}', [FeeRateController::class, 'destroy'])->name('fee-rates.destroy')->middleware('permission:fee-rates.delete');

        Route::get('/fee-policies', [FeePolicyController::class, 'index'])->name('fee-policies.index')->middleware('permission:fee-policies.view');
        Route::get('/fee-policies/create', [FeePolicyController::class, 'create'])->name('fee-policies.create')->middleware('permission:fee-policies.create');
        Route::post('/fee-policies', [FeePolicyController::class, 'store'])->name('fee-policies.store')->middleware('permission:fee-policies.create');
        Route::get('/fee-policies/{feePolicy}/edit', [FeePolicyController::class, 'edit'])->name('fee-policies.edit')->middleware('permission:fee-policies.edit');
        Route::put('/fee-policies/{feePolicy}', [FeePolicyController::class, 'update'])->name('fee-policies.update')->middleware('permission:fee-policies.edit');
        Route::delete('/fee-policies/{feePolicy}', [FeePolicyController::class, 'destroy'])->name('fee-policies.destroy')->middleware('permission:fee-policies.delete');

        Route::get('/student-fee-policies', [StudentFeePolicyController::class, 'index'])->name('student-fee-policies.index')->middleware('permission:student-fee-policies.view');
        Route::get('/student-fee-policies/create', [StudentFeePolicyController::class, 'create'])->name('student-fee-policies.create')->middleware('permission:student-fee-policies.create');
        Route::post('/student-fee-policies', [StudentFeePolicyController::class, 'store'])->name('student-fee-policies.store')->middleware('permission:student-fee-policies.create');
        Route::get('/student-fee-policies/{studentFeePolicy}/edit', [StudentFeePolicyController::class, 'edit'])->name('student-fee-policies.edit')->middleware('permission:student-fee-policies.edit');
        Route::put('/student-fee-policies/{studentFeePolicy}', [StudentFeePolicyController::class, 'update'])->name('student-fee-policies.update')->middleware('permission:student-fee-policies.edit');
        Route::delete('/student-fee-policies/{studentFeePolicy}', [StudentFeePolicyController::class, 'destroy'])->name('student-fee-policies.destroy')->middleware('permission:student-fee-policies.delete');

        Route::get('/fees', [FeeAssessmentController::class, 'index'])->name('fees.index')->middleware('permission:fees.view');
        Route::get('/fees/generate', [FeeAssessmentController::class, 'create'])->name('fees.generate.create')->middleware('permission:fees.generate');
        Route::post('/fees/generate', [FeeAssessmentController::class, 'generate'])->name('fees.generate')->middleware('permission:fees.generate');
        Route::get('/fees/students/{student}', [FeeAssessmentController::class, 'studentLedger'])->name('fees.students.show')->middleware('permission:fees.view');
        Route::get('/fees/{feeAssessment}', [FeeAssessmentController::class, 'show'])->name('fees.show')->middleware('permission:fees.view');
        Route::post('/fees/{feeAssessment}/payments', [FeePaymentController::class, 'store'])->name('fees.payments.store')->middleware('permission:fees.pay');
        Route::post('/fees/{feeAssessment}/cancel', [FeeAssessmentController::class, 'cancel'])->name('fees.cancel')->middleware('permission:fees.cancel');
        Route::post('/fee-payments/{feePayment}/cancel', [FeePaymentController::class, 'cancel'])->name('fee-payments.cancel')->middleware('permission:fees.cancel');
        Route::get('/reports/fees', [FinanceReportController::class, 'fees'])->name('reports.fees')->middleware('permission:fees.report');

        Route::get('/salary-components', [SalaryComponentController::class, 'index'])->name('salary-components.index')->middleware('permission:salary-components.view');
        Route::get('/salary-components/create', [SalaryComponentController::class, 'create'])->name('salary-components.create')->middleware('permission:salary-components.create');
        Route::post('/salary-components', [SalaryComponentController::class, 'store'])->name('salary-components.store')->middleware('permission:salary-components.create');
        Route::get('/salary-components/{salaryComponent}/edit', [SalaryComponentController::class, 'edit'])->name('salary-components.edit')->middleware('permission:salary-components.edit');
        Route::put('/salary-components/{salaryComponent}', [SalaryComponentController::class, 'update'])->name('salary-components.update')->middleware('permission:salary-components.edit');
        Route::delete('/salary-components/{salaryComponent}', [SalaryComponentController::class, 'destroy'])->name('salary-components.destroy')->middleware('permission:salary-components.delete');

        Route::get('/salary-profiles', [SalaryProfileController::class, 'index'])->name('salary-profiles.index')->middleware('permission:salary-configs.view');
        Route::get('/salary-profiles/create', [SalaryProfileController::class, 'create'])->name('salary-profiles.create')->middleware('permission:salary-configs.create');
        Route::post('/salary-profiles', [SalaryProfileController::class, 'store'])->name('salary-profiles.store')->middleware('permission:salary-configs.create');
        Route::get('/salary-profiles/{salaryProfile}/edit', [SalaryProfileController::class, 'edit'])->name('salary-profiles.edit')->middleware('permission:salary-configs.edit');
        Route::put('/salary-profiles/{salaryProfile}', [SalaryProfileController::class, 'update'])->name('salary-profiles.update')->middleware('permission:salary-configs.edit');
        Route::delete('/salary-profiles/{salaryProfile}', [SalaryProfileController::class, 'destroy'])->name('salary-profiles.destroy')->middleware('permission:salary-configs.delete');

        Route::get('/payroll-inputs', [PayrollInputController::class, 'index'])->name('payroll-inputs.index')->middleware('permission:payroll-inputs.view');
        Route::get('/payroll-inputs/create', [PayrollInputController::class, 'create'])->name('payroll-inputs.create')->middleware('permission:payroll-inputs.create');
        Route::post('/payroll-inputs', [PayrollInputController::class, 'store'])->name('payroll-inputs.store')->middleware('permission:payroll-inputs.create');
        Route::get('/payroll-inputs/{payrollInput}/edit', [PayrollInputController::class, 'edit'])->name('payroll-inputs.edit')->middleware('permission:payroll-inputs.edit');
        Route::put('/payroll-inputs/{payrollInput}', [PayrollInputController::class, 'update'])->name('payroll-inputs.update')->middleware('permission:payroll-inputs.edit');

        Route::get('/teaching-extra', [TeachingExtraController::class, 'index'])->name('teaching-extra.index')->middleware('permission:teaching-extra.view');
        Route::get('/teaching-extra/create', [TeachingExtraController::class, 'create'])->name('teaching-extra.create')->middleware('permission:teaching-extra.create');
        Route::post('/teaching-extra', [TeachingExtraController::class, 'store'])->name('teaching-extra.store')->middleware('permission:teaching-extra.create');
        Route::post('/teaching-extra/{teachingExtra}/approve', [TeachingExtraController::class, 'approve'])->name('teaching-extra.approve')->middleware('permission:teaching-extra.approve');

        Route::get('/tax-configs', [TaxConfigController::class, 'index'])->name('tax-configs.index')->middleware('permission:tax-configs.view');
        Route::get('/tax-configs/create', [TaxConfigController::class, 'create'])->name('tax-configs.create')->middleware('permission:tax-configs.create');
        Route::post('/tax-configs', [TaxConfigController::class, 'store'])->name('tax-configs.store')->middleware('permission:tax-configs.create');
        Route::get('/tax-configs/{taxConfig}/edit', [TaxConfigController::class, 'edit'])->name('tax-configs.edit')->middleware('permission:tax-configs.edit');
        Route::put('/tax-configs/{taxConfig}', [TaxConfigController::class, 'update'])->name('tax-configs.update')->middleware('permission:tax-configs.edit');
        Route::delete('/tax-configs/{taxConfig}', [TaxConfigController::class, 'destroy'])->name('tax-configs.destroy')->middleware('permission:tax-configs.delete');

        Route::get('/insurance-configs', [InsuranceConfigController::class, 'index'])->name('insurance-configs.index')->middleware('permission:insurance-configs.view');
        Route::get('/insurance-configs/create', [InsuranceConfigController::class, 'create'])->name('insurance-configs.create')->middleware('permission:insurance-configs.create');
        Route::post('/insurance-configs', [InsuranceConfigController::class, 'store'])->name('insurance-configs.store')->middleware('permission:insurance-configs.create');
        Route::get('/insurance-configs/{insuranceConfig}/edit', [InsuranceConfigController::class, 'edit'])->name('insurance-configs.edit')->middleware('permission:insurance-configs.edit');
        Route::put('/insurance-configs/{insuranceConfig}', [InsuranceConfigController::class, 'update'])->name('insurance-configs.update')->middleware('permission:insurance-configs.edit');
        Route::delete('/insurance-configs/{insuranceConfig}', [InsuranceConfigController::class, 'destroy'])->name('insurance-configs.destroy')->middleware('permission:insurance-configs.delete');

        Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index')->middleware('permission:payrolls.view');
        Route::get('/payrolls/generate', [PayrollController::class, 'create'])->name('payrolls.generate.create')->middleware('permission:payrolls.calculate');
        Route::post('/payrolls/generate', [PayrollController::class, 'generate'])->name('payrolls.generate')->middleware('permission:payrolls.calculate');
        Route::get('/payrolls/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show')->middleware('permission:payrolls.view');
        Route::post('/payrolls/{payroll}/calculate', [PayrollController::class, 'calculate'])->name('payrolls.calculate')->middleware('permission:payrolls.calculate');
        Route::post('/payrolls/{payroll}/approve', [PayrollController::class, 'approve'])->name('payrolls.approve')->middleware('permission:payrolls.approve');
        Route::post('/payrolls/{payroll}/lock', [PayrollController::class, 'lock'])->name('payrolls.lock')->middleware('permission:payrolls.lock');
        Route::post('/payrolls/{payroll}/cancel', [PayrollController::class, 'cancel'])->name('payrolls.cancel')->middleware('permission:payrolls.cancel');
        Route::post('/payrolls/{payroll}/payments', [PayrollPaymentController::class, 'store'])->name('payrolls.payments.store')->middleware('permission:payrolls.pay');
        Route::post('/payroll-payments/{payrollPayment}/cancel', [PayrollPaymentController::class, 'cancel'])->name('payroll-payments.cancel')->middleware('permission:payrolls.cancel');
        Route::post('/payrolls/{payroll}/adjustments', [PayrollController::class, 'adjustment'])->name('payrolls.adjustments.store')->middleware('permission:payrolls.adjust');
        Route::post('/payroll-adjustments/{payrollAdjustment}/approve', [PayrollController::class, 'approveAdjustment'])->name('payroll-adjustments.approve')->middleware('permission:payrolls.adjust');
        Route::post('/payroll-adjustments/{payrollAdjustment}/lock', [PayrollController::class, 'lockAdjustment'])->name('payroll-adjustments.lock')->middleware('permission:payrolls.adjust');
        Route::post('/payroll-adjustments/{payrollAdjustment}/settle', [PayrollController::class, 'settleAdjustment'])->name('payroll-adjustments.settle')->middleware('permission:payrolls.adjust');
        Route::post('/payroll-adjustments/{payrollAdjustment}/cancel', [PayrollController::class, 'cancelAdjustment'])->name('payroll-adjustments.cancel')->middleware('permission:payrolls.adjust');
        Route::get('/reports/payroll', [FinanceReportController::class, 'payroll'])->name('reports.payroll')->middleware('permission:payrolls.report');
    });

/*
| Dynamic Menu Routes
*/

Route::middleware(['auth', 'backoffice'])
    ->prefix('workspace')
    ->name('dynamic.menus.')
    ->group(function () {

        Route::get('/{menu:slug}', [DynamicMenuController::class, 'show'])
            ->name('show');

        Route::get('/{menu:slug}/records/create', [DynamicMenuController::class, 'create'])
            ->name('records.create');

        Route::post('/{menu:slug}/records', [DynamicMenuController::class, 'store'])
            ->name('records.store');

        Route::get('/{menu:slug}/records/{record}/edit', [DynamicMenuController::class, 'edit'])
            ->name('records.edit');

        Route::put('/{menu:slug}/records/{record}', [DynamicMenuController::class, 'update'])
            ->name('records.update');

        Route::delete('/{menu:slug}/records/{record}', [DynamicMenuController::class, 'destroy'])
            ->name('records.destroy');
    });
