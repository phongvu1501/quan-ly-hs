<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schedules', 'week_start')) {
            Schema::table('schedules', function (Blueprint $table): void {
                $table->date('week_start')->nullable()->after('semester_id')->index();
            });
        }

        DB::table('schedules')->whereNull('week_start')->orderBy('id')->eachById(function ($schedule): void {
            $date = $schedule->created_at ? new DateTimeImmutable($schedule->created_at) : new DateTimeImmutable('today');
            DB::table('schedules')->where('id', $schedule->id)->update([
                'week_start' => $date->modify('monday this week')->format('Y-m-d'),
            ]);
        });

        try {
            Schema::table('schedules', function (Blueprint $table): void {
                $table->dropUnique(['semester_id', 'class_id', 'weekday', 'period']);
            });
        } catch (Throwable) {
            // The unique index may have a driver-specific generated name.
        }
        Schema::table('schedules', function (Blueprint $table): void {
            $table->unique(['semester_id', 'class_id', 'weekday', 'period', 'week_start'], 'schedule_week_unique');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropUnique('schedule_week_unique');
            $table->unique(['semester_id', 'class_id', 'weekday', 'period']);
            $table->dropColumn('week_start');
        });
    }
};
