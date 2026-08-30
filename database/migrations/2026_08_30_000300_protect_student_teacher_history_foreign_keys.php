<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceDeleteRule('scores', 'student_id', 'students', 'restrict');
        $this->replaceDeleteRule('attendance_records', 'student_id', 'students', 'restrict');
        $this->replaceDeleteRule('exam_results', 'student_id', 'students', 'restrict');
        $this->replaceDeleteRule('subject_results', 'student_id', 'students', 'restrict');
        $this->replaceDeleteRule('conduct_evaluations', 'student_id', 'students', 'restrict');
        $this->replaceDeleteRule('teacher_comments', 'student_id', 'students', 'restrict');

        $this->replaceDeleteRule('classes', 'homeroom_teacher_id', 'teachers', 'restrict');
        $this->replaceDeleteRule('schedules', 'teacher_id', 'teachers', 'restrict');
        $this->replaceDeleteRule('scores', 'teacher_id', 'teachers', 'restrict');
        $this->replaceDeleteRule('teacher_subject', 'teacher_id', 'teachers', 'restrict');

        $this->replaceDeleteRule('scores', 'assignment_id', 'teaching_assignments', 'restrict');
        $this->replaceDeleteRule('teaching_extra_records', 'teaching_assignment_id', 'teaching_assignments', 'restrict');
    }

    public function down(): void
    {
        $this->replaceDeleteRule('teaching_extra_records', 'teaching_assignment_id', 'teaching_assignments', 'null');
        $this->replaceDeleteRule('scores', 'assignment_id', 'teaching_assignments', 'null');

        $this->replaceDeleteRule('teacher_subject', 'teacher_id', 'teachers', 'cascade');
        $this->replaceDeleteRule('scores', 'teacher_id', 'teachers', 'null');
        $this->replaceDeleteRule('schedules', 'teacher_id', 'teachers', 'null');
        $this->replaceDeleteRule('classes', 'homeroom_teacher_id', 'teachers', 'null');

        $this->replaceDeleteRule('teacher_comments', 'student_id', 'students', 'cascade');
        $this->replaceDeleteRule('conduct_evaluations', 'student_id', 'students', 'cascade');
        $this->replaceDeleteRule('subject_results', 'student_id', 'students', 'cascade');
        $this->replaceDeleteRule('exam_results', 'student_id', 'students', 'cascade');
        $this->replaceDeleteRule('attendance_records', 'student_id', 'students', 'cascade');
        $this->replaceDeleteRule('scores', 'student_id', 'students', 'cascade');
    }

    private function replaceDeleteRule(string $tableName, string $column, string $parentTable, string $rule): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($column, $parentTable, $rule): void {
            $table->dropForeign([$column]);
            $foreign = $table->foreign($column)->references('id')->on($parentTable);
            match ($rule) {
                'cascade' => $foreign->cascadeOnDelete(),
                'null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
    }
};
