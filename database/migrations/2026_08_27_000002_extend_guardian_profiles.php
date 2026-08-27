<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table): void {
            $table->string('phone', 30)->nullable()->change();
            $table->date('date_of_birth')->nullable()->after('full_name');
            $table->string('religion', 100)->nullable()->after('occupation');
            $table->string('workplace')->nullable()->after('religion');
            $table->text('notes')->nullable()->after('workplace');
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table): void {
            $table->dropColumn(['date_of_birth', 'religion', 'workplace', 'notes']);
            $table->string('phone', 30)->nullable(false)->change();
        });
    }
};
