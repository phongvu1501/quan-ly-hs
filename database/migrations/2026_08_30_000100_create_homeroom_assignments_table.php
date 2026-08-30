<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homeroom_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->index(['teacher_id', 'academic_year_id', 'status'], 'homeroom_teacher_year_status_index');
            $table->index(['class_id', 'status'], 'homeroom_class_status_index');
        });

        DB::statement("ALTER TABLE homeroom_assignments ADD CONSTRAINT homeroom_assignments_status_check CHECK (status IN ('active','inactive'))");

        $now = now();
        DB::table('classes')->whereNotNull('homeroom_teacher_id')->orderBy('id')->chunkById(200, function ($classes) use ($now): void {
            DB::table('homeroom_assignments')->insert($classes->map(fn ($class) => [
                'teacher_id' => $class->homeroom_teacher_id,
                'class_id' => $class->id,
                'academic_year_id' => $class->academic_year_id,
                'started_on' => null,
                'ended_on' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('homeroom_assignments');
    }
};
