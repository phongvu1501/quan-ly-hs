<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = [
        'fee_assessment_id',
        'payment_code',
        'fee_notice_id',
        'student_id',
        'amount',
        'payment_date',
        'payment_method',
        'status',
        'paid_at',
        'transaction_code',
        'receipt_number',
        'note',
        'created_by',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
            'paid_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function isLegacy(): bool
    {
        return $this->fee_assessment_id === null;
    }
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(FeeAssessment::class, 'fee_assessment_id');
    }
    public function feeNotice(): BelongsTo
    {
        return $this->belongsTo(FeeNotice::class);
    }
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
