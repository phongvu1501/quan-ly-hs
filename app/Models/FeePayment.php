<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = ['fee_notice_id', 'student_id', 'amount', 'status', 'paid_at', 'transaction_code', 'note'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'paid_at' => 'datetime']; }
    public function feeNotice(): BelongsTo { return $this->belongsTo(FeeNotice::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
