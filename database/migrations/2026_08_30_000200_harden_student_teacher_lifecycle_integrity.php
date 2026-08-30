<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertNoDuplicateUserLinks('students');
        $this->assertNoDuplicateUserLinks('teachers');
        $duplicateAssignment = DB::table('teaching_assignments')
            ->selectRaw('academic_year_id, COALESCE(semester_id, 0) semester_scope, class_id, subject_id, teacher_id, COUNT(*) total')
            ->groupBy('academic_year_id', DB::raw('COALESCE(semester_id, 0)'), 'class_id', 'subject_id', 'teacher_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($duplicateAssignment) {
            throw new RuntimeException('Không thể thêm unique assignment: dữ liệu hiện tại có phân công trùng, bao gồm phạm vi Cả năm.');
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->unique('user_id', 'students_user_id_unique');
            $table->index('admission_year', 'students_admission_year_index');
        });
        Schema::table('teachers', function (Blueprint $table): void {
            $table->unique('user_id', 'teachers_user_id_unique');
        });

        DB::statement("ALTER TABLE students ADD CONSTRAINT students_status_check CHECK (status IN ('studying','sick_leave','reserved','dropped','graduated'))");
        DB::statement("ALTER TABLE teachers ADD CONSTRAINT teachers_status_check CHECK (status IN ('active','on_leave','inactive'))");
        DB::statement("ALTER TABLE enrollments ADD CONSTRAINT enrollments_status_check CHECK (status IN ('active','inactive','completed','withdrawn'))");
        DB::statement("ALTER TABLE teaching_assignments ADD CONSTRAINT teaching_assignments_status_check CHECK (status IN ('active','inactive'))");

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropForeign(['semester_id']);
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->index('academic_year_id', 'teaching_assignments_academic_year_id_index');
            $table->dropUnique('assignment_unique');
        });
        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->unsignedBigInteger('semester_scope_id')->default(0)->after('semester_id');
        });
        DB::table('teaching_assignments')->update([
            'semester_scope_id' => DB::raw('COALESCE(semester_id, 0)'),
        ]);
        DB::statement('ALTER TABLE teaching_assignments ADD CONSTRAINT teaching_assignments_semester_scope_check CHECK (semester_scope_id = COALESCE(semester_id, 0))');
        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->unique(
                ['academic_year_id', 'semester_scope_id', 'class_id', 'subject_id', 'teacher_id'],
                'assignment_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropUnique('assignment_unique');
        });
        DB::statement('ALTER TABLE teaching_assignments DROP CHECK teaching_assignments_semester_scope_check');
        DB::statement('ALTER TABLE teaching_assignments DROP COLUMN semester_scope_id');
        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropForeign(['semester_id']);
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
            $table->unique(
                ['academic_year_id', 'semester_id', 'class_id', 'subject_id', 'teacher_id'],
                'assignment_unique'
            );
            $table->dropIndex('teaching_assignments_academic_year_id_index');
        });

        DB::statement('ALTER TABLE teaching_assignments DROP CHECK teaching_assignments_status_check');
        DB::statement('ALTER TABLE enrollments DROP CHECK enrollments_status_check');
        DB::statement('ALTER TABLE teachers DROP CHECK teachers_status_check');
        DB::statement('ALTER TABLE students DROP CHECK students_status_check');
        Schema::table('teachers', fn (Blueprint $table) => $table->dropUnique('teachers_user_id_unique'));
        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique('students_user_id_unique');
            $table->dropIndex('students_admission_year_index');
        });
    }

    private function assertNoDuplicateUserLinks(string $table): void
    {
        $duplicates = DB::table($table)
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($duplicates) {
            throw new RuntimeException("Không thể thêm unique {$table}.user_id: dữ liệu hiện tại có user_id trùng.");
        }
    }
};
