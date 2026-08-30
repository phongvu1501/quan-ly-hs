<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_notices', function (Blueprint $table): void {
            $table->foreignId('fee_category_id')->nullable()->after('class_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('fee_rate_id')->nullable()->after('fee_category_id')
                ->constrained()->restrictOnDelete();
            $table->string('notice_code', 80)->nullable()->after('id')->unique();
            $table->string('generation_key', 64)->nullable()->after('notice_code')->unique();
            $table->string('period_type', 30)->nullable()->after('amount');
            $table->string('period_key', 80)->nullable()->after('period_type');
            $table->unsignedSmallInteger('charge_sequence')->nullable()->after('period_key');
            $table->date('assessment_date')->nullable()->after('charge_sequence');
            $table->string('scope_type', 30)->nullable()->after('assessment_date');
            $table->string('status', 30)->nullable()->after('is_active');
            $table->foreignId('generated_by')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable()->after('generated_by');
            $table->index(
                ['academic_year_id', 'fee_category_id', 'period_key'],
                'fee_notice_generation_lookup_index'
            );
        });

        $academicYearForeign = $this->foreignKeyName('fee_notices', 'academic_year_id');
        if ($academicYearForeign !== null) {
            Schema::table('fee_notices', function (Blueprint $table) use ($academicYearForeign): void {
                $table->dropForeign($academicYearForeign);
            });
        }

        Schema::table('fee_notices', function (Blueprint $table): void {
            $table->foreign('academic_year_id', 'fee_notices_academic_year_finance_foreign')
                ->references('id')->on('academic_years')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $academicYearForeign = $this->foreignKeyName('fee_notices', 'academic_year_id');
        if ($academicYearForeign !== null) {
            Schema::table('fee_notices', function (Blueprint $table) use ($academicYearForeign): void {
                $table->dropForeign($academicYearForeign);
            });
        }

        Schema::table('fee_notices', function (Blueprint $table): void {
            $table->foreign('academic_year_id', 'fee_notices_academic_year_id_foreign')
                ->references('id')->on('academic_years')->cascadeOnDelete();
            $table->dropIndex('fee_notice_generation_lookup_index');
            $table->dropForeign(['fee_category_id']);
            $table->dropForeign(['fee_rate_id']);
            $table->dropForeign(['generated_by']);
            $table->dropUnique(['notice_code']);
            $table->dropUnique(['generation_key']);
            $table->dropColumn([
                'notice_code', 'generation_key', 'fee_category_id', 'fee_rate_id',
                'period_type', 'period_key', 'charge_sequence', 'assessment_date',
                'scope_type', 'status', 'generated_by', 'generated_at',
            ]);
        });
    }

    private function foreignKeyName(string $table, string $column): ?string
    {
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? '
            .'AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            [$table, $column]
        );

        return $row?->CONSTRAINT_NAME;
    }
};
