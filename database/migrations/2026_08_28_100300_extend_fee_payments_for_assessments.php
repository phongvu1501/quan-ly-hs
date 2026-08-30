<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $noticeForeign = $this->foreignKeyName('fee_payments', 'fee_notice_id');
        $studentForeign = $this->foreignKeyName('fee_payments', 'student_id');
        $legacyUnique = $this->uniqueIndexForColumns(
            'fee_payments',
            ['fee_notice_id', 'student_id']
        );

        Schema::table('fee_payments', function (Blueprint $table) use ($noticeForeign, $studentForeign): void {
            if ($noticeForeign !== null) {
                $table->dropForeign($noticeForeign);
            }
            if ($studentForeign !== null) {
                $table->dropForeign($studentForeign);
            }
        });

        if ($legacyUnique !== null) {
            Schema::table('fee_payments', function (Blueprint $table) use ($legacyUnique): void {
                $table->dropUnique($legacyUnique);
            });
        }

        Schema::table('fee_payments', function (Blueprint $table): void {
            $table->foreignId('fee_notice_id')->nullable()->change();
            $table->foreignId('fee_assessment_id')->nullable()->after('id')
                ->constrained()->restrictOnDelete();
            $table->string('payment_code', 80)->nullable()->after('fee_assessment_id')->unique();
            $table->dateTime('payment_date')->nullable()->after('amount');
            $table->string('payment_method', 30)->nullable()->after('payment_date');
            $table->string('receipt_number', 100)->nullable()->after('transaction_code');
            $table->foreignId('created_by')->nullable()->after('note')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('reversed_at')->nullable()->after('reversed_by');
            $table->text('reversal_reason')->nullable()->after('reversed_at');

            $table->foreign('fee_notice_id', 'fee_payments_legacy_notice_foreign')
                ->references('id')->on('fee_notices')->restrictOnDelete();
            $table->foreign('student_id', 'fee_payments_student_finance_foreign')
                ->references('id')->on('students')->restrictOnDelete();
            $table->index(
                ['fee_notice_id', 'student_id'],
                'fee_payments_legacy_notice_student_index'
            );
            $table->index(
                ['fee_assessment_id', 'status'],
                'fee_payments_assessment_status_index'
            );
            $table->index(['student_id', 'payment_date'], 'fee_payments_student_date_index');
        });
    }

    public function down(): void
    {
        if (DB::table('fee_payments')->whereNotNull('fee_assessment_id')->exists()) {
            throw new RuntimeException('Cannot roll back fee payment schema while assessment payments exist.');
        }

        Schema::table('fee_payments', function (Blueprint $table): void {
            $table->dropForeign(['fee_assessment_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['reversed_by']);
            $table->dropForeign('fee_payments_legacy_notice_foreign');
            $table->dropForeign('fee_payments_student_finance_foreign');
            $table->dropIndex('fee_payments_legacy_notice_student_index');
            $table->dropIndex('fee_payments_assessment_status_index');
            $table->dropIndex('fee_payments_student_date_index');
            $table->dropUnique(['payment_code']);
            $table->dropColumn([
                'fee_assessment_id',
                'payment_code',
                'payment_date',
                'payment_method',
                'receipt_number',
                'created_by',
                'reversed_by',
                'reversed_at',
                'reversal_reason',
            ]);
        });

        Schema::table('fee_payments', function (Blueprint $table): void {
            $table->foreignId('fee_notice_id')->nullable(false)->change();
            $table->foreign('fee_notice_id', 'fee_payments_fee_notice_id_foreign')
                ->references('id')->on('fee_notices')->cascadeOnDelete();
            $table->foreign('student_id', 'fee_payments_student_id_foreign')
                ->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['fee_notice_id', 'student_id']);
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

    /** @param list<string> $columns */
    private function uniqueIndexForColumns(string $table, array $columns): ?string
    {
        $indexes = DB::select(
            'SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns '
                .'FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '
                .'AND NON_UNIQUE = 0 AND INDEX_NAME <> ? GROUP BY INDEX_NAME',
            [$table, 'PRIMARY']
        );
        $expected = implode(',', $columns);

        foreach ($indexes as $index) {
            if ($index->indexed_columns === $expected) {
                return $index->INDEX_NAME;
            }
        }

        return null;
    }
};
