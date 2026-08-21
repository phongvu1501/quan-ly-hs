<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'homeroom.dashboard' => 'Homeroom teacher dashboard',
            'subject_teacher.dashboard' => 'Subject teacher dashboard',
        ] as $slug => $name) {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $name, 'description' => $name]);
        }

        $homeroom = Role::firstOrCreate(
            ['slug' => 'homeroom_teacher'],
            ['name' => 'Homeroom teacher', 'description' => 'Teacher responsible for a class']
        );
        $homeroom->permissions()->syncWithoutDetaching(
            Permission::whereIn('slug', ['homeroom.dashboard', 'teacher.dashboard', 'scores.manage', 'scores.view', 'attendance.manage', 'announcements.view', 'fees.view'])->pluck('id')
        );

        $subjectTeacher = Role::firstOrCreate(
            ['slug' => 'subject_teacher'],
            ['name' => 'Subject teacher', 'description' => 'Teacher assigned to subjects']
        );
        $subjectTeacher->permissions()->syncWithoutDetaching(
            Permission::whereIn('slug', ['subject_teacher.dashboard', 'teacher.dashboard', 'scores.manage', 'scores.view', 'attendance.manage', 'announcements.view', 'fees.view'])->pluck('id')
        );
    }

    public function down(): void
    {
        // Keep role permissions when accounts have been assigned to them.
    }
};
