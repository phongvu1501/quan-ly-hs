<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_payroll_inputs', function (Blueprint $table): void {
            $table->id();
            $table->string('input_code', 80)->unique();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salary_component_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('input_year');
            $table->unsignedTinyInteger('input_month');
            $table->string('input_type', 40);
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('input_amount', 15, 2)->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['teacher_id', 'input_year', 'input_month', 'status'], 'teacher_payroll_input_period_index');
            $table->index(['salary_component_id', 'status']);
        });

        Schema::create('teaching_extra_records', function (Blueprint $table): void {
            $table->id();
            $table->string('record_code', 80)->unique();
            $table->string('record_key', 64)->unique();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('teaching_assignment_id')->nullable()
                ->constrained('teaching_assignments')->nullOnDelete();
            $table->foreignId('teacher_salary_config_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('teaching_year');
            $table->unsignedTinyInteger('teaching_month');
            $table->decimal('number_of_periods', 8, 2);
            $table->decimal('rate_per_period', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('subject_code_snapshot', 30);
            $table->string('subject_name_snapshot');
            $table->string('class_code_snapshot', 30);
            $table->string('class_name_snapshot', 100);
            $table->string('status', 20)->default('draft');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['teacher_id', 'teaching_year', 'teaching_month', 'status'], 'teaching_extra_period_index');
            $table->index(
                ['academic_year_id', 'semester_id', 'subject_id'],
                'teaching_extra_academic_subject_index'
            );
        });

        DB::statement('ALTER TABLE teacher_payroll_inputs ADD CONSTRAINT teacher_payroll_inputs_values_check CHECK (input_month BETWEEN 1 AND 12 AND (quantity IS NULL OR quantity >= 0) AND (input_amount IS NULL OR input_amount >= 0))');
        DB::statement("ALTER TABLE teacher_payroll_inputs ADD CONSTRAINT teacher_payroll_inputs_status_check CHECK (status IN ('draft', 'approved', 'cancelled'))");
        DB::statement('ALTER TABLE teaching_extra_records ADD CONSTRAINT teaching_extra_values_check CHECK (teaching_month BETWEEN 1 AND 12 AND number_of_periods > 0 AND rate_per_period >= 0 AND total_amount = number_of_periods * rate_per_period)');
        DB::statement("ALTER TABLE teaching_extra_records ADD CONSTRAINT teaching_extra_status_check CHECK (status IN ('draft', 'approved', 'cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_extra_records');
        Schema::dropIfExists('teacher_payroll_inputs');
    }
};
