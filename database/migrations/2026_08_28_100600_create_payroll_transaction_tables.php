<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table): void {
            $table->id();
            $table->string('payroll_code', 80)->unique();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_salary_profile_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('payroll_year');
            $table->unsignedTinyInteger('payroll_month');

            $table->string('teacher_code_snapshot', 30);
            $table->string('teacher_name_snapshot');
            $table->string('specialization_snapshot')->nullable();
            $table->string('academic_year_snapshot', 50)->nullable();
            $table->string('salary_profile_code_snapshot', 80)->nullable();

            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('total_teaching_extra', 15, 2)->default(0);
            $table->decimal('total_bonus', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_insurance', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->text('note')->nullable();

            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('calculated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('locked_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'payroll_year', 'payroll_month'], 'payroll_teacher_period_unique');
            $table->index(['payroll_year', 'payroll_month', 'status'], 'payroll_period_status_index');
            $table->index(['specialization_snapshot', 'status'], 'payroll_specialization_status_index');
        });

        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->restrictOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('line_key', 120);
            $table->string('component_code', 80);
            $table->string('component_name');
            $table->string('type', 20);
            $table->string('category', 40);
            $table->string('calculation_type', 30);
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('rate', 15, 2)->nullable();
            $table->decimal('percentage_value', 7, 4)->nullable();
            $table->decimal('calculation_base_amount', 15, 2)->nullable();
            $table->decimal('amount', 15, 2);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_insurance_base')->default(false);
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('subject_code_snapshot', 30)->nullable();
            $table->string('subject_name_snapshot')->nullable();
            $table->string('class_code_snapshot', 30)->nullable();
            $table->string('class_name_snapshot', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['payroll_id', 'line_key'], 'payroll_item_line_unique');
            $table->index(['payroll_id', 'type', 'category']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('payroll_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_code', 80)->unique();
            $table->foreignId('payroll_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->dateTime('payment_date');
            $table->string('payment_method', 30);
            $table->string('receipt_number', 100)->nullable();
            $table->string('status', 20)->default('completed');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->index(['payroll_id', 'status']);
            $table->index(['teacher_id', 'payment_date']);
        });

        Schema::create('payroll_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->string('adjustment_code', 80)->unique();
            $table->foreignId('payroll_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('component_code_snapshot', 80);
            $table->string('component_name_snapshot');
            $table->string('type', 20);
            $table->decimal('amount', 15, 2);
            $table->text('reason');
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('locked_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('settled_at')->nullable();
            $table->string('settlement_method', 30)->nullable();
            $table->string('settlement_reference', 100)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['payroll_id', 'status']);
        });

        Schema::table('teacher_payroll_inputs', function (Blueprint $table): void {
            $table->foreignId('included_payroll_id')->nullable()->after('salary_component_id')
                ->constrained('payrolls')->restrictOnDelete();
        });
        Schema::table('teaching_extra_records', function (Blueprint $table): void {
            $table->foreignId('included_payroll_id')->nullable()->after('teacher_salary_config_id')
                ->constrained('payrolls')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE payrolls ADD CONSTRAINT payroll_amounts_check CHECK (payroll_month BETWEEN 1 AND 12 AND base_salary >= 0 AND total_allowances >= 0 AND total_teaching_extra >= 0 AND total_bonus >= 0 AND gross_salary >= 0 AND total_insurance >= 0 AND total_tax >= 0 AND total_deductions >= 0 AND total_deductions <= gross_salary AND net_salary = gross_salary - total_deductions AND net_salary >= 0 AND paid_amount >= 0 AND paid_amount <= net_salary AND remaining_amount = net_salary - paid_amount)');
        DB::statement("ALTER TABLE payrolls ADD CONSTRAINT payroll_status_check CHECK (status IN ('draft', 'calculated', 'approved', 'locked', 'paid', 'cancelled'))");
        DB::statement("ALTER TABLE payroll_items ADD CONSTRAINT payroll_item_type_check CHECK (type IN ('earning', 'deduction'))");
        DB::statement('ALTER TABLE payroll_items ADD CONSTRAINT payroll_item_amount_check CHECK (amount >= 0 AND (quantity IS NULL OR quantity >= 0) AND (rate IS NULL OR rate >= 0) AND (percentage_value IS NULL OR (percentage_value >= 0 AND percentage_value <= 100)) AND (calculation_base_amount IS NULL OR calculation_base_amount >= 0))');
        DB::statement('ALTER TABLE payroll_payments ADD CONSTRAINT payroll_payment_amount_check CHECK (amount > 0)');
        DB::statement("ALTER TABLE payroll_payments ADD CONSTRAINT payroll_payment_status_check CHECK (status IN ('completed', 'reversed', 'cancelled'))");
        DB::statement("ALTER TABLE payroll_adjustments ADD CONSTRAINT payroll_adjustment_values_check CHECK (type IN ('earning', 'deduction') AND amount > 0 AND status IN ('draft', 'approved', 'locked', 'paid', 'cancelled'))");
    }

    public function down(): void
    {
        if (DB::table('teacher_payroll_inputs')->whereNotNull('included_payroll_id')->exists()
            || DB::table('teaching_extra_records')->whereNotNull('included_payroll_id')->exists()) {
            throw new RuntimeException('Cannot roll back payroll schema while payroll inputs are included.');
        }

        Schema::table('teaching_extra_records', function (Blueprint $table): void {
            $table->dropForeign(['included_payroll_id']);
            $table->dropColumn('included_payroll_id');
        });
        Schema::table('teacher_payroll_inputs', function (Blueprint $table): void {
            $table->dropForeign(['included_payroll_id']);
            $table->dropColumn('included_payroll_id');
        });

        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
    }
};
