<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\TeachingAssignment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Quản trị viên', 'description' => 'Toàn quyền quản trị hệ thống']
        );

        $admin->permissions()->syncWithoutDetaching(Permission::pluck('id'));

        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->make(['name' => 'Test User'])->only(['name', 'password'])
        );
        $user->roles()->syncWithoutDetaching([$admin->id]);

        $hang = User::updateOrCreate(
            ['email' => 'hang@gmail.com'],
            [
                'name' => 'Hang',
                'password' => Hash::make('123456'),
                'must_change_password' => false,
            ]
        );
        $hang->roles()->sync(
            Role::whereIn('slug', ['admin', 'teacher', 'student', 'homeroom_teacher', 'subject_teacher'])->pluck('id')
        );

        $hangStudent = Student::firstOrCreate(
            ['student_code' => 'HANG-TEST'],
            [
                'full_name' => 'Hang Test',
                'email' => 'hang@gmail.com',
                'status' => 'studying',
            ]
        );
        $hangStudent->update(['user_id' => $hang->id]);

        $startYear = now()->year;
        $academicYear = AcademicYear::firstOrCreate(
            ['code' => $startYear.'-'.($startYear + 1)],
            [
                'name' => $startYear.'-'.($startYear + 1),
                'starts_on' => $startYear.'-08-15',
                'ends_on' => ($startYear + 1).'-05-31',
                'is_current' => true,
            ]
        );
        Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'number' => 1],
            ['name' => 'Semester 1', 'starts_on' => $startYear.'-08-15', 'ends_on' => $startYear.'-12-31', 'is_current' => true]
        );
        Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'number' => 2],
            ['name' => 'Semester 2', 'starts_on' => ($startYear + 1).'-01-01', 'ends_on' => ($startYear + 1).'-05-31', 'is_current' => false]
        );

        foreach ([10, 11, 12] as $level) {
            Grade::firstOrCreate(
                ['level' => $level],
                ['name' => 'Grade '.$level]
            );
        }

        foreach ([
            ['code' => 'MATH', 'name' => 'Mathematics', 'short_name' => 'Math'],
            ['code' => 'LIT', 'name' => 'Literature', 'short_name' => 'Literature'],
            ['code' => 'ENG', 'name' => 'English', 'short_name' => 'English'],
            ['code' => 'PHYS', 'name' => 'Physics', 'short_name' => 'Physics'],
            ['code' => 'CHEM', 'name' => 'Chemistry', 'short_name' => 'Chemistry'],
            ['code' => 'BIO', 'name' => 'Biology', 'short_name' => 'Biology'],
            ['code' => 'HIST', 'name' => 'History', 'short_name' => 'History'],
            ['code' => 'GEO', 'name' => 'Geography', 'short_name' => 'Geography'],
            ['code' => 'CIVIC', 'name' => 'Civic Education', 'short_name' => 'Civic'],
            ['code' => 'IT', 'name' => 'Information Technology', 'short_name' => 'IT'],
            ['code' => 'PE', 'name' => 'Physical Education', 'short_name' => 'PE'],
        ] as $subject) {
            Subject::firstOrCreate(['code' => $subject['code']], $subject);
        }

        $hangTeacher = Teacher::where('user_id', $hang->id)->first();
        if (!$hangTeacher) {
            $hangTeacher = Teacher::firstOrCreate(
                ['teacher_code' => 'HANG-TEST'],
                ['full_name' => 'Hang Test', 'email' => 'hang@gmail.com', 'status' => 'active', 'user_id' => $hang->id]
            );
            $hangTeacher->update(['user_id' => $hang->id]);
        }

        $now = now();
        $subjectRows = Subject::pluck('id')->map(fn (int $subjectId) => [
            'teacher_id' => $hangTeacher->id,
            'subject_id' => $subjectId,
            'academic_year_id' => $academicYear->id,
            'is_primary' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        DB::table('teacher_subject')->upsert(
            $subjectRows,
            ['teacher_id', 'subject_id', 'academic_year_id'],
            ['is_primary', 'updated_at']
        );

        $grade10 = Grade::where('level', 10)->first();
        if ($grade10 && $hangTeacher) {
            $classA = SchoolClass::firstOrCreate(
                ['academic_year_id' => $academicYear->id, 'code' => 'HANG-10A1'],
                [
                    'grade_id' => $grade10->id,
                    'homeroom_teacher_id' => $hangTeacher->id,
                    'name' => '10A1 - Lớp kiểm thử',
                    'capacity' => 45,
                    'status' => 'active',
                ]
            );

            $classB = SchoolClass::firstOrCreate(
                ['academic_year_id' => $academicYear->id, 'code' => 'HANG-10A2'],
                [
                    'grade_id' => $grade10->id,
                    'homeroom_teacher_id' => $hangTeacher->id,
                    'name' => '10A2 - Lớp demo',
                    'room' => 'A102',
                    'capacity' => 45,
                    'status' => 'active',
                ]
            );

            $grade11 = Grade::where('level', 11)->first();
            $classC = $grade11 ? SchoolClass::firstOrCreate(
                ['academic_year_id' => $academicYear->id, 'code' => 'HANG-11A1'],
                [
                    'grade_id' => $grade11->id,
                    'homeroom_teacher_id' => $hangTeacher->id,
                    'name' => '11A1 - Lớp demo',
                    'room' => 'B201',
                    'capacity' => 45,
                    'status' => 'active',
                ]
            ) : null;

            $semester = Semester::where('academic_year_id', $academicYear->id)->where('number', 1)->first();
            $subjects = Subject::whereIn('code', ['MATH', 'LIT', 'ENG', 'PHYS', 'BIO'])->get()->keyBy('code');
            $demoScheduleRows = [
                [$classA, 'MATH', 1, 1, 'A101'],
                [$classA, 'LIT', 3, 2, 'A101'],
                [$classB, 'ENG', 2, 1, 'A102'],
                [$classB, 'PHYS', 4, 3, 'Phòng thí nghiệm'],
                [$classC, 'BIO', 5, 2, 'B201'],
            ];

            foreach ($demoScheduleRows as [$class, $subjectCode, $weekday, $period, $room]) {
                if (!$class || !$semester || !$subjects->get($subjectCode)) continue;

                $assignment = TeachingAssignment::updateOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'semester_id' => $semester->id,
                        'class_id' => $class->id,
                        'subject_id' => $subjects->get($subjectCode)->id,
                        'teacher_id' => $hangTeacher->id,
                    ],
                    ['status' => 'active']
                );

                Schedule::updateOrCreate(
                    [
                        'semester_id' => $semester->id,
                        'class_id' => $class->id,
                        'weekday' => $weekday,
                        'period' => $period,
                    ],
                    [
                        'academic_year_id' => $academicYear->id,
                        'subject_id' => $subjects->get($subjectCode)->id,
                        'teacher_id' => $hangTeacher->id,
                        'room' => $room,
                        'notes' => 'Dữ liệu demo liên kết lớp - môn - giáo viên.',
                    ]
                );
            }

            $hangStudent->enrollments()->updateOrCreate(
                ['academic_year_id' => $academicYear->id],
                ['class_id' => $classA->id, 'status' => 'active', 'enrolled_on' => today()]
            );
        }
    }
}
