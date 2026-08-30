<?php

namespace App\Services\Finance;

use App\Models\FeeAssessment;
use App\Models\FeePayment;
use App\Models\User;
use App\Support\Decimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeePaymentService
{
    public function __construct(private readonly FinanceAuditService $audit) {}

    /** @param array<string, mixed> $input */
    public function record(FeeAssessment $assessment, array $input, User $actor): FeePayment
    {
        return DB::transaction(function () use ($assessment, $input, $actor): FeePayment {
            $locked = FeeAssessment::query()->lockForUpdate()->findOrFail($assessment->id);
            if ($locked->status === 'cancelled') {
                throw ValidationException::withMessages(['assessment' => 'Khoản phải thu đã bị hủy.']);
            }

            $amount = Decimal::money($input['amount']);
            if (! Decimal::isPositive($amount)) {
                throw ValidationException::withMessages(['amount' => 'Số tiền thanh toán phải lớn hơn 0.']);
            }
            if (Decimal::compare($amount, $locked->remaining_amount) > 0) {
                throw ValidationException::withMessages(['amount' => 'Số tiền vượt quá công nợ còn lại.']);
            }

            $paymentDate = Carbon::parse($input['payment_date'], config('app.timezone'));
            $payment = FeePayment::create([
                'fee_assessment_id' => $locked->id,
                'payment_code' => 'FP-'.Str::upper((string) Str::ulid()),
                'fee_notice_id' => null,
                'student_id' => $locked->student_id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $input['payment_method'],
                'status' => 'completed',
                'paid_at' => $paymentDate,
                'transaction_code' => $input['transaction_code'] ?? null,
                'receipt_number' => $input['receipt_number'] ?? null,
                'note' => $input['note'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->refreshAssessmentTotals($locked);
            $this->audit->record($payment, 'fee_payment.recorded', $actor, newValues: [
                'payment_code' => $payment->payment_code,
                'assessment_id' => $locked->id,
                'amount' => $amount,
                'payment_date' => $paymentDate->toDateTimeString(),
                'payment_method' => $payment->payment_method,
            ]);

            return $payment->fresh();
        });
    }

    public function cancel(FeePayment $payment, string $reason, User $actor): FeePayment
    {
        return DB::transaction(function () use ($payment, $reason, $actor): FeePayment {
            $lockedPayment = FeePayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->isLegacy()) {
                throw ValidationException::withMessages([
                    'payment' => 'Giao dịch legacy chỉ được tra cứu, không thể hủy bằng workflow mới.',
                ]);
            }
            if ($lockedPayment->status !== 'completed') {
                throw ValidationException::withMessages(['payment' => 'Chỉ giao dịch hợp lệ mới có thể hủy.']);
            }

            $assessment = FeeAssessment::query()->lockForUpdate()->findOrFail($lockedPayment->fee_assessment_id);
            $lockedPayment->update([
                'status' => 'cancelled', 'reversed_by' => $actor->id,
                'reversed_at' => now(), 'reversal_reason' => $reason,
            ]);
            $this->refreshAssessmentTotals($assessment);
            $this->audit->record(
                $lockedPayment,
                'fee_payment.cancelled',
                $actor,
                ['status' => 'completed'],
                ['status' => 'cancelled', 'reversal_reason' => $reason]
            );

            return $lockedPayment->fresh();
        });
    }

    private function refreshAssessmentTotals(FeeAssessment $assessment): void
    {
        $paid = Decimal::money((string) $assessment->payments()->where('status', 'completed')->sum('amount'));
        $remaining = Decimal::subtract($assessment->final_amount, $paid);
        if (Decimal::isNegative($remaining)) {
            throw ValidationException::withMessages(['amount' => 'Tổng thanh toán vượt quá số tiền phải thu.']);
        }

        $status = Decimal::compare($remaining, '0.00') === 0
            ? 'paid'
            : (Decimal::isPositive($paid) ? 'partially_paid' : 'pending');

        $assessment->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $status,
        ]);
    }
}
