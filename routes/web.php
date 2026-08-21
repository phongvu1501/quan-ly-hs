<?php

use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DynamicMenuController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\TeacherPortalController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {

    // Register
    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register']);

    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/', function () {

    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();
        $target = app(\App\Http\Controllers\AuthController::class)->dashboardUrl($user);

        return $target === route('dashboard')
            ? view('admin.layouts.app')
            : redirect()->to($target);
    })->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/teacher/dashboard', [TeacherPortalController::class, 'dashboard'])
        ->name('teacher.dashboard')->middleware('permission:teacher.dashboard');
    Route::get('/teacher/homeroom', [TeacherPortalController::class, 'homeroomDashboard'])
        ->name('teacher.homeroom.dashboard')->middleware('permission:homeroom.dashboard');
    Route::get('/teacher/subject', [TeacherPortalController::class, 'subjectDashboard'])
        ->name('teacher.subject.dashboard')->middleware('permission:teacher.dashboard');
    Route::get('/teacher/scores', [TeacherPortalController::class, 'scores'])
        ->name('teacher.scores')->middleware('permission:scores.manage');
    Route::post('/teacher/scores', [TeacherPortalController::class, 'storeScore'])
        ->name('teacher.scores.store')->middleware('permission:scores.manage');
    Route::put('/teacher/scores/{score}', [TeacherPortalController::class, 'updateScore'])
        ->name('teacher.scores.update')->middleware('permission:scores.manage');
    Route::get('/teacher/attendance', [TeacherPortalController::class, 'attendance'])
        ->name('teacher.attendance')->middleware('permission:attendance.manage');
    Route::post('/teacher/attendance', [TeacherPortalController::class, 'storeAttendance'])
        ->name('teacher.attendance.store')->middleware('permission:attendance.manage');
    Route::put('/teacher/attendance/{attendance}', [TeacherPortalController::class, 'updateAttendance'])
        ->name('teacher.attendance.update')->middleware('permission:attendance.manage');
    Route::get('/teacher/comments', [TeacherPortalController::class, 'comments'])
        ->name('teacher.comments')->middleware('permission:scores.manage');
    Route::post('/teacher/comments', [TeacherPortalController::class, 'storeComment'])
        ->name('teacher.comments.store')->middleware('permission:scores.manage');
    Route::put('/teacher/comments/{comment}', [TeacherPortalController::class, 'updateComment'])
        ->name('teacher.comments.update')->middleware('permission:scores.manage');

    Route::get('/student/dashboard', [StudentPortalController::class, 'dashboard'])
        ->name('student.dashboard')->middleware('permission:student.dashboard');
    Route::get('/student/payments', [StudentPortalController::class, 'payments'])
        ->name('student.payments')->middleware('permission:student.dashboard');
});

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

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
            ->middleware('permission:roles.manage');

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
            ->middleware('permission:users.manage');

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
            ->middleware('permission:permissions.manage');

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
            ->middleware('permission:menus.manage');

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

Route::middleware('auth')->prefix('admin/school')->name('admin.school.')->group(function () {
    Route::get('/', [SchoolController::class, 'dashboard'])->name('dashboard')->middleware('permission:school.dashboard');

    Route::get('/students', [SchoolController::class, 'students'])->name('students.index')->middleware('permission:students.manage');
    Route::get('/students/create', [SchoolController::class, 'createStudent'])->name('students.create')->middleware('permission:students.create');
    Route::post('/students', [SchoolController::class, 'storeStudent'])->name('students.store')->middleware('permission:students.create');
    Route::get('/students/{student}/payments', [SchoolController::class, 'studentPayments'])->name('students.payments')->middleware('permission:fees.manage');
    Route::post('/students/{student}/payments', [SchoolController::class, 'storeStudentPayment'])->name('students.payments.store')->middleware('permission:fees.manage');
    Route::get('/students/{student}/transcript', [SchoolController::class, 'studentTranscript'])->name('students.transcript')->middleware('permission:students.manage');
    Route::get('/students/{student}/edit', [SchoolController::class, 'editStudent'])->name('students.edit')->middleware('permission:students.edit');
    Route::put('/students/{student}', [SchoolController::class, 'updateStudent'])->name('students.update')->middleware('permission:students.edit');
    Route::delete('/students/{student}', [SchoolController::class, 'destroyStudent'])->name('students.destroy')->middleware('permission:students.delete');

    Route::get('/teachers', [SchoolController::class, 'teachers'])->name('teachers.index')->middleware('permission:teachers.manage');
    Route::get('/teachers/homeroom', [SchoolController::class, 'homeroomTeachers'])->name('teachers.homeroom')->middleware('permission:teachers.manage');
    Route::get('/teachers/subject', [SchoolController::class, 'subjectTeachers'])->name('teachers.subject')->middleware('permission:teachers.manage');
    Route::get('/teachers/create', [SchoolController::class, 'createTeacher'])->name('teachers.create')->middleware('permission:teachers.create');
    Route::post('/teachers', [SchoolController::class, 'storeTeacher'])->name('teachers.store')->middleware('permission:teachers.create');
    Route::get('/teachers/{teacher}/edit', [SchoolController::class, 'editTeacher'])->name('teachers.edit')->middleware('permission:teachers.edit');
    Route::put('/teachers/{teacher}', [SchoolController::class, 'updateTeacher'])->name('teachers.update')->middleware('permission:teachers.edit');
    Route::get('/teachers/{teacher}/history', [SchoolController::class, 'teacherHistory'])->name('teachers.history')->middleware('permission:teachers.manage');
    Route::delete('/teachers/{teacher}', [SchoolController::class, 'destroyTeacher'])->name('teachers.destroy')->middleware('permission:teachers.delete');

    Route::get('/grades', [SchoolController::class, 'grades'])->name('grades.index')->middleware('permission:grades.manage');
    Route::get('/grades/create', [SchoolController::class, 'createGrade'])->name('grades.create')->middleware('permission:grades.manage');
    Route::post('/grades', [SchoolController::class, 'storeGrade'])->name('grades.store')->middleware('permission:grades.manage');
    Route::get('/grades/{grade}/edit', [SchoolController::class, 'editGrade'])->name('grades.edit')->middleware('permission:grades.manage');
    Route::put('/grades/{grade}', [SchoolController::class, 'updateGrade'])->name('grades.update')->middleware('permission:grades.manage');
    Route::delete('/grades/{grade}', [SchoolController::class, 'destroyGrade'])->name('grades.destroy')->middleware('permission:grades.manage');

    Route::get('/subjects', [SchoolController::class, 'subjects'])->name('subjects.index')->middleware('permission:subjects.manage');
    Route::get('/subjects/create', [SchoolController::class, 'createSubject'])->name('subjects.create')->middleware('permission:subjects.manage');
    Route::post('/subjects', [SchoolController::class, 'storeSubject'])->name('subjects.store')->middleware('permission:subjects.manage');
    Route::get('/subjects/{subject}/edit', [SchoolController::class, 'editSubject'])->name('subjects.edit')->middleware('permission:subjects.manage');
    Route::get('/subjects/{subject}/teachers/{teacher}', [SchoolController::class, 'subjectTeacherDetail'])->name('subjects.teachers.show')->middleware('permission:teachers.manage');
    Route::put('/subjects/{subject}', [SchoolController::class, 'updateSubject'])->name('subjects.update')->middleware('permission:subjects.manage');
    Route::delete('/subjects/{subject}', [SchoolController::class, 'destroySubject'])->name('subjects.destroy')->middleware('permission:subjects.manage');

    Route::get('/classes', [SchoolController::class, 'classes'])->name('classes.index')->middleware('permission:classes.manage');
    Route::get('/classes/create', [SchoolController::class, 'createClass'])->name('classes.create')->middleware('permission:classes.manage');
    Route::post('/classes', [SchoolController::class, 'storeClass'])->name('classes.store')->middleware('permission:classes.manage');
    Route::get('/classes/{schoolClass}/edit', [SchoolController::class, 'editClass'])->name('classes.edit')->middleware('permission:classes.manage');
    Route::put('/classes/{schoolClass}', [SchoolController::class, 'updateClass'])->name('classes.update')->middleware('permission:classes.manage');
    Route::delete('/classes/{schoolClass}', [SchoolController::class, 'destroyClass'])->name('classes.destroy')->middleware('permission:classes.manage');

    Route::get('/assignments', [SchoolController::class, 'assignments'])->name('assignments.index')->middleware('permission:assignments.manage');
    Route::post('/assignments', [SchoolController::class, 'storeAssignment'])->name('assignments.store')->middleware('permission:assignments.manage');
    Route::delete('/assignments/{assignment}', [SchoolController::class, 'destroyAssignment'])->name('assignments.destroy')->middleware('permission:assignments.manage');

    Route::get('/scores', [SchoolController::class, 'scores'])->name('scores.index')->middleware('permission:scores.manage');
    Route::get('/attendance', [SchoolController::class, 'attendance'])->name('attendance.index')->middleware('permission:attendance.manage');
    Route::get('/schedules', [SchoolController::class, 'schedules'])->name('schedules.index')->middleware('permission:schedules.manage');
    Route::post('/schedules', [SchoolController::class, 'storeSchedule'])->name('schedules.store')->middleware('permission:schedules.manage');
    Route::put('/schedules/{schedule}', [SchoolController::class, 'updateSchedule'])->name('schedules.update')->middleware('permission:schedules.manage');
    Route::delete('/schedules/{schedule}', [SchoolController::class, 'destroySchedule'])->name('schedules.destroy')->middleware('permission:schedules.manage');

    Route::get('/exams', [SchoolController::class, 'exams'])->name('exams.index')->middleware('permission:exams.manage');
    Route::get('/exams/create', [SchoolController::class, 'createExam'])->name('exams.create')->middleware('permission:exams.manage');
    Route::post('/exams', [SchoolController::class, 'storeExam'])->name('exams.store')->middleware('permission:exams.manage');
    Route::get('/exams/{exam}/edit', [SchoolController::class, 'editExam'])->name('exams.edit')->middleware('permission:exams.manage');
    Route::put('/exams/{exam}', [SchoolController::class, 'updateExam'])->name('exams.update')->middleware('permission:exams.manage');
    Route::delete('/exams/{exam}', [SchoolController::class, 'destroyExam'])->name('exams.destroy')->middleware('permission:exams.manage');

    Route::get('/exam-invigilations', [SchoolController::class, 'examInvigilations'])->name('exam-invigilations.index')->middleware('permission:exams.manage');
    Route::get('/exam-invigilations/create', [SchoolController::class, 'createExamInvigilation'])->name('exam-invigilations.create')->middleware('permission:exams.manage');
    Route::post('/exam-invigilations', [SchoolController::class, 'storeExamInvigilation'])->name('exam-invigilations.store')->middleware('permission:exams.manage');
    Route::get('/exam-invigilations/{examInvigilation}/edit', [SchoolController::class, 'editExamInvigilation'])->name('exam-invigilations.edit')->middleware('permission:exams.manage');
    Route::put('/exam-invigilations/{examInvigilation}', [SchoolController::class, 'updateExamInvigilation'])->name('exam-invigilations.update')->middleware('permission:exams.manage');
    Route::delete('/exam-invigilations/{examInvigilation}', [SchoolController::class, 'destroyExamInvigilation'])->name('exam-invigilations.destroy')->middleware('permission:exams.manage');

    Route::get('/announcements', [SchoolController::class, 'announcements'])->name('announcements.index')->middleware('permission:announcements.manage');
    Route::get('/announcements/create', [SchoolController::class, 'createAnnouncement'])->name('announcements.create')->middleware('permission:announcements.manage');
    Route::post('/announcements', [SchoolController::class, 'storeAnnouncement'])->name('announcements.store')->middleware('permission:announcements.manage');
    Route::get('/announcements/{announcement}/edit', [SchoolController::class, 'editAnnouncement'])->name('announcements.edit')->middleware('permission:announcements.manage');
    Route::put('/announcements/{announcement}', [SchoolController::class, 'updateAnnouncement'])->name('announcements.update')->middleware('permission:announcements.manage');
    Route::delete('/announcements/{announcement}', [SchoolController::class, 'destroyAnnouncement'])->name('announcements.destroy')->middleware('permission:announcements.manage');

    Route::get('/fees', [SchoolController::class, 'fees'])->name('fees.index')->middleware('permission:fees.manage');
    Route::get('/fees/create', [SchoolController::class, 'createFee'])->name('fees.create')->middleware('permission:fees.manage');
    Route::post('/fees', [SchoolController::class, 'storeFee'])->name('fees.store')->middleware('permission:fees.manage');
    Route::get('/fees/{fee}/edit', [SchoolController::class, 'editFee'])->name('fees.edit')->middleware('permission:fees.manage');
    Route::put('/fees/{fee}', [SchoolController::class, 'updateFee'])->name('fees.update')->middleware('permission:fees.manage');
    Route::delete('/fees/{fee}', [SchoolController::class, 'destroyFee'])->name('fees.destroy')->middleware('permission:fees.manage');
});

/*
|----------------------------------------------------------------------
| DYNAMIC MENU WORKSPACE
|----------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('workspace')->name('dynamic.menus.')->group(function () {
    Route::get('/{menu:slug}', [DynamicMenuController::class, 'show'])->name('show');
    Route::get('/{menu:slug}/records/create', [DynamicMenuController::class, 'create'])->name('records.create');
    Route::post('/{menu:slug}/records', [DynamicMenuController::class, 'store'])->name('records.store');
    Route::get('/{menu:slug}/records/{record}/edit', [DynamicMenuController::class, 'edit'])->name('records.edit');
    Route::put('/{menu:slug}/records/{record}', [DynamicMenuController::class, 'update'])->name('records.update');
    Route::delete('/{menu:slug}/records/{record}', [DynamicMenuController::class, 'destroy'])->name('records.destroy');
});
