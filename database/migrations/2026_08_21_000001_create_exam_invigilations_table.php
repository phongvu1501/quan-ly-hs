<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_invigilations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->string('role', 30)->default('invigilator');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'teacher_id']);
            $table->index(['teacher_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_invigilations');
    }
};
