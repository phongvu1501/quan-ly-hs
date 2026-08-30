<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeePolicy extends Model
{
    protected $fillable = [
        'student_id',
        'fee_policy_id',
        'effective_from',
        'effective_to',
        'status',
        'evidence_reference',
        'note',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(FeePolicy::class, 'fee_policy_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
