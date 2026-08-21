<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'school.dashboard' => 'School dashboard',
            'students.manage' => 'Manage students',
            'students.create' => 'Create students',
            'students.edit' => 'Edit students',
            'students.delete' => 'Delete students',
            'teachers.manage' => 'Manage teachers',
            'teachers.create' => 'Create teachers',
            'teachers.edit' => 'Edit teachers',
            'teachers.delete' => 'Delete teachers',
            'grades.manage' => 'Manage grade levels',
            'subjects.manage' => 'Manage subjects',
            'classes.manage' => 'Manage classes',
            'assignments.manage' => 'Manage teaching assignments',
            'scores.manage' => 'Manage scores',
            'scores.view' => 'View scores',
            'attendance.manage' => 'Manage attendance',
            'schedules.manage' => 'Manage schedules',
            'exams.manage' => 'Manage exams',
            'announcements.manage' => 'Manage announcements',
            'announcements.view' => 'View announcements',
            'fees.manage' => 'Manage tuition notices',
            'fees.view' => 'View tuition notices',
            'teacher.dashboard' => 'Teacher dashboard',
            'student.dashboard' => 'Student dashboard',
        ];

        $ids = collect($permissions)->map(function (string $name, string $slug): int {
            return Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $name]
            )->id;
        });

        Role::where('slug', 'admin')->each(
            fn (Role $role) => $role->permissions()->syncWithoutDetaching($ids)
        );

        $teacher = Role::firstOrCreate(
            ['slug' => 'teacher'],
            ['name' => 'Teacher', 'description' => 'Teaching staff']
        );
        $teacher->permissions()->syncWithoutDetaching(
            Permission::whereIn('slug', [
                'teacher.dashboard', 'scores.manage', 'scores.view', 'attendance.manage',
                'announcements.view', 'fees.view',
            ])->pluck('id')
        );

        $student = Role::firstOrCreate(
            ['slug' => 'student'],
            ['name' => 'Student', 'description' => 'Student account']
        );
        $student->permissions()->syncWithoutDetaching(
            Permission::whereIn('slug', [
                'student.dashboard', 'scores.view', 'announcements.view', 'fees.view',
            ])->pluck('id')
        );
    }

    public function down(): void
    {
        // School permissions are intentionally retained for existing roles.
    }
};
