<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeNotice extends Model
{
    protected $fillable = ['academic_year_id', 'semester_id', 'class_id', 'title', 'amount', 'due_on', 'description', 'is_active'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'due_on' => 'date', 'is_active' => 'boolean']; }
    public function payments(): HasMany { return $this->hasMany(FeePayment::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
}
