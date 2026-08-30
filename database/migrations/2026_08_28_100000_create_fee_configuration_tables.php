<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('type', 30)->default('other');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('fee_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('period_type', 30);
            $table->decimal('amount', 15, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(
                ['fee_category_id', 'academic_year_id', 'period_type', 'effective_from'],
                'fee_rate_effective_unique'
            );
            $table->index(['academic_year_id', 'period_type', 'is_active']);
        });

        Schema::create('fee_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('reduction_type', 30);
            $table->decimal('reduction_value', 15, 4);
            $table->string('application_mode', 20)->default('exclusive');
            $table->integer('priority')->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['academic_year_id', 'fee_category_id', 'status'], 'fee_policy_lookup_index');
            $table->index(['priority', 'application_mode']);
        });

        Schema::create('student_fee_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_policy_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('evidence_reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(
                ['student_id', 'fee_policy_id', 'effective_from'],
                'student_fee_policy_effective_unique'
            );
            $table->index(['student_id', 'status', 'effective_from'], 'student_fee_policy_lookup_index');
        });

        DB::statement('ALTER TABLE fee_rates ADD CONSTRAINT fee_rates_amount_check CHECK (amount >= 0)');
        DB::statement('ALTER TABLE fee_rates ADD CONSTRAINT fee_rates_dates_check CHECK (effective_to IS NULL OR effective_to >= effective_from)');
        DB::statement("ALTER TABLE fee_policies ADD CONSTRAINT fee_policies_type_check CHECK (reduction_type IN ('percentage', 'fixed_amount', 'full_exemption'))");
        DB::statement("ALTER TABLE fee_policies ADD CONSTRAINT fee_policies_mode_check CHECK (application_mode IN ('exclusive', 'cumulative'))");
        DB::statement("ALTER TABLE fee_policies ADD CONSTRAINT fee_policies_value_check CHECK ((reduction_type = 'percentage' AND reduction_value >= 0 AND reduction_value <= 100) OR (reduction_type = 'fixed_amount' AND reduction_value >= 0) OR (reduction_type = 'full_exemption' AND reduction_value = 100))");
        DB::statement('ALTER TABLE fee_policies ADD CONSTRAINT fee_policies_dates_check CHECK (effective_to IS NULL OR effective_to >= effective_from)');
        DB::statement('ALTER TABLE student_fee_policies ADD CONSTRAINT student_fee_policies_dates_check CHECK (effective_to IS NULL OR effective_to >= effective_from)');
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_policies');
        Schema::dropIfExists('fee_policies');
        Schema::dropIfExists('fee_rates');
        Schema::dropIfExists('fee_categories');
    }
};
