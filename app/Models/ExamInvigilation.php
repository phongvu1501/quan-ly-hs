<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamInvigilation extends Model
{
    protected $fillable = ['exam_id', 'teacher_id', 'role', 'notes'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
