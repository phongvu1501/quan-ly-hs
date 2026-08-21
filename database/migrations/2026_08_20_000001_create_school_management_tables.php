<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 30);
            $table->string('code', 30)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('semesters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('name', 50);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->unique(['academic_year_id', 'number']);
        });

        Schema::create('grades', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();
            $table->string('name', 50);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('teacher_code', 30)->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('address')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('specialization')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'full_name']);
        });

        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('short_name', 50)->nullable();
            $table->unsignedTinyInteger('weekly_periods')->default(2);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('room', 50)->nullable();
            $table->unsignedSmallInteger('capacity')->default(45);
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['academic_year_id', 'code']);
            $table->index(['academic_year_id', 'grade_id']);
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('student_code', 30)->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('admission_year', 20)->nullable();
            $table->string('status', 30)->default('studying');
            $table->string('avatar')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'full_name']);
        });

        Schema::create('guardians', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('occupation')->nullable();
            $table->timestamps();
        });

        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 30)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->primary(['guardian_id', 'student_id']);
        });

        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('roll_number')->nullable();
            $table->string('status', 30)->default('active');
            $table->date('enrolled_on')->nullable();
            $table->date('left_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['academic_year_id', 'student_id']);
            $table->index(['class_id', 'status']);
        });

        Schema::create('teaching_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['academic_year_id', 'semester_id', 'class_id', 'subject_id', 'teacher_id'], 'assignment_unique');
            $table->index(['teacher_id', 'academic_year_id']);
        });

        Schema::create('scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('teaching_assignments')->nullOnDelete();
            $table->string('score_type', 30);
            $table->decimal('score', 4, 2);
            $table->date('assessed_on')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'subject_id', 'semester_id']);
            $table->index(['class_id', 'subject_id', 'score_type']);
        });

        Schema::create('subject_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->decimal('average_score', 4, 2)->nullable();
            $table->string('rating', 30)->nullable();
            $table->text('teacher_comment')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'subject_id', 'semester_id']);
        });

        Schema::create('teacher_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->text('content');
            $table->timestamps();
            $table->index(['student_id', 'semester_id']);
        });

        Schema::create('conduct_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('rating', 30)->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'semester_id']);
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attended_on');
            $table->unsignedTinyInteger('period')->nullable();
            $table->string('status', 30);
            $table->string('reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'attended_on', 'period', 'subject_id'], 'attendance_unique');
            $table->index(['class_id', 'attended_on']);
        });

        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->unsignedTinyInteger('period');
            $table->string('room', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['semester_id', 'class_id', 'weekday', 'period']);
            $table->index(['teacher_id', 'semester_id']);
        });

        Schema::create('exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('exam_type', 30)->default('midterm');
            $table->date('exam_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('room', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['semester_id', 'exam_date']);
        });

        Schema::create('exam_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 4, 2)->nullable();
            $table->string('status', 30)->default('present');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['exam_id', 'student_id']);
        });

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('type', 30)->default('general');
            $table->string('audience', 30)->default('all');
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['audience', 'is_published', 'published_at']);
        });

        Schema::create('fee_notices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('due_on')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fee_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->string('transaction_code', 80)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['fee_notice_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('fee_notices');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('conduct_evaluations');
        Schema::dropIfExists('teacher_comments');
        Schema::dropIfExists('subject_results');
        Schema::dropIfExists('scores');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
    }
};
