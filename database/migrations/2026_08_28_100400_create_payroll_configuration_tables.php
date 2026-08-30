<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('type', 20);
            $table->string('category', 40);
            $table->string('calculation_type', 30);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_insurance_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['type', 'category', 'is_active']);
        });

        Schema::create('teacher_salary_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('profile_code', 80)->unique();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->unsignedSmallInteger('dependent_count')->default(0);
            $table->boolean('tax_enabled')->default(false);
            $table->boolean('insurance_enabled')->default(false);
            $table->string('status', 20)->default('draft');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['teacher_id', 'effective_from'], 'teacher_salary_profile_effective_unique');
            $table->index(['teacher_id', 'status', 'effective_from'], 'teacher_salary_profile_lookup_index');
        });

        Schema::create('teacher_salary_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_salary_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('salary_component_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope_key', 100);
            $table->decimal('fixed_amount', 15, 2)->nullable();
            $table->decimal('percentage_value', 7, 4)->nullable();
            $table->decimal('rate_per_unit', 15, 2)->nullable();
            $table->decimal('default_quantity', 10, 2)->nullable();
            $table->string('calculation_base', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(
                ['teacher_salary_profile_id', 'salary_component_id', 'scope_key'],
                'teacher_salary_config_scope_unique'
            );
            $table->index(['salary_component_id', 'is_active']);
        });

        Schema::create('tax_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('tax_year');
            $table->decimal('personal_deduction', 15, 2)->default(0);
            $table->decimal('dependent_deduction', 15, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['tax_year', 'is_active', 'effective_from']);
        });

        Schema::create('tax_brackets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_config_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->decimal('lower_bound', 15, 2);
            $table->decimal('upper_bound', 15, 2)->nullable();
            $table->decimal('rate_percentage', 7, 4);
            $table->decimal('quick_deduction', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['tax_config_id', 'sequence']);
        });

        Schema::create('insurance_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->decimal('employee_rate_percentage', 7, 4);
            $table->decimal('employer_rate_percentage', 7, 4)->default(0);
            $table->string('salary_base_type', 40);
            $table->decimal('fixed_salary_base', 15, 2)->nullable();
            $table->decimal('maximum_salary_base', 15, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'effective_from']);
        });

        DB::statement("ALTER TABLE salary_components ADD CONSTRAINT salary_components_type_check CHECK (type IN ('earning', 'deduction'))");
        DB::statement("ALTER TABLE salary_components ADD CONSTRAINT salary_components_calculation_check CHECK (calculation_type IN ('fixed', 'percentage', 'per_unit', 'manual'))");
        DB::statement("ALTER TABLE teacher_salary_profiles ADD CONSTRAINT teacher_salary_profiles_status_check CHECK (status IN ('draft', 'active', 'inactive'))");
        DB::statement('ALTER TABLE teacher_salary_profiles ADD CONSTRAINT teacher_salary_profiles_dates_check CHECK (effective_to IS NULL OR effective_to >= effective_from)');
        DB::statement('ALTER TABLE teacher_salary_configs ADD CONSTRAINT teacher_salary_configs_values_check CHECK ((fixed_amount IS NULL OR fixed_amount >= 0) AND (percentage_value IS NULL OR (percentage_value >= 0 AND percentage_value <= 100)) AND (rate_per_unit IS NULL OR rate_per_unit >= 0) AND (default_quantity IS NULL OR default_quantity >= 0))');
        DB::statement('ALTER TABLE tax_configs ADD CONSTRAINT tax_configs_values_check CHECK (personal_deduction >= 0 AND dependent_deduction >= 0 AND (effective_to IS NULL OR effective_to >= effective_from))');
        DB::statement('ALTER TABLE tax_brackets ADD CONSTRAINT tax_brackets_values_check CHECK (lower_bound >= 0 AND (upper_bound IS NULL OR upper_bound > lower_bound) AND rate_percentage >= 0 AND rate_percentage <= 100 AND quick_deduction >= 0)');
        DB::statement('ALTER TABLE insurance_configs ADD CONSTRAINT insurance_configs_values_check CHECK (employee_rate_percentage >= 0 AND employee_rate_percentage <= 100 AND employer_rate_percentage >= 0 AND employer_rate_percentage <= 100 AND (fixed_salary_base IS NULL OR fixed_salary_base >= 0) AND (maximum_salary_base IS NULL OR maximum_salary_base >= 0) AND (effective_to IS NULL OR effective_to >= effective_from))');
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_configs');
        Schema::dropIfExists('tax_brackets');
        Schema::dropIfExists('tax_configs');
        Schema::dropIfExists('teacher_salary_configs');
        Schema::dropIfExists('teacher_salary_profiles');
        Schema::dropIfExists('salary_components');
    }
};
