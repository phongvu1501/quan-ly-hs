<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_assessments', function (Blueprint $table): void {
            $table->id();
            $table->string('assessment_code', 80)->unique();
            $table->foreignId('fee_notice_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('fee_category_id')->constrained()->restrictOnDelete();
            $table->string('period_type', 30);
            $table->string('period_key', 80);
            $table->unsignedSmallInteger('charge_sequence');
            $table->date('assessment_date');
            $table->date('due_date')->nullable();

            $table->string('student_code_snapshot', 30);
            $table->string('student_name_snapshot');
            $table->string('class_code_snapshot', 30)->nullable();
            $table->string('class_name_snapshot', 100)->nullable();
            $table->string('fee_category_code_snapshot', 50);
            $table->string('fee_category_name_snapshot');
            $table->string('fee_notice_title_snapshot');

            $table->decimal('original_amount', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->string('status', 30)->default('pending');
            $table->text('note')->nullable();

            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'academic_year_id', 'fee_category_id', 'period_key', 'charge_sequence'],
                'fee_assessment_business_unique'
            );
            $table->unique(['student_id', 'fee_notice_id'], 'fee_assessment_notice_student_unique');
            $table->index(['academic_year_id', 'semester_id', 'status'], 'fee_assessment_period_status_index');
            $table->index(['class_id', 'fee_category_id', 'due_date'], 'fee_assessment_report_index');
            $table->index(['student_id', 'remaining_amount'], 'fee_assessment_debt_index');
        });

        Schema::create('fee_assessment_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_assessment_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_policy_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('policy_code', 80);
            $table->string('policy_name');
            $table->string('reduction_type', 30);
            $table->decimal('reduction_value', 15, 4);
            $table->string('application_mode', 20);
            $table->integer('priority');
            $table->date('policy_effective_from');
            $table->date('policy_effective_to')->nullable();
            $table->decimal('calculated_discount', 15, 2);
            $table->timestamps();
            $table->unique(['fee_assessment_id', 'sequence'], 'fee_assessment_discount_sequence_unique');
            $table->index(['fee_policy_id', 'fee_assessment_id']);
        });

        DB::statement('ALTER TABLE fee_assessments ADD CONSTRAINT fee_assessment_amounts_check CHECK (original_amount >= 0 AND discount_amount >= 0 AND discount_amount <= original_amount AND final_amount = original_amount - discount_amount AND paid_amount >= 0 AND paid_amount <= final_amount AND remaining_amount = final_amount - paid_amount AND remaining_amount >= 0)');
        DB::statement("ALTER TABLE fee_assessments ADD CONSTRAINT fee_assessment_status_check CHECK (status IN ('pending', 'partially_paid', 'paid', 'cancelled'))");
        DB::statement('ALTER TABLE fee_assessment_discounts ADD CONSTRAINT fee_assessment_discount_amount_check CHECK (reduction_value >= 0 AND calculated_discount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_assessment_discounts');
        Schema::dropIfExists('fee_assessments');
    }
};
