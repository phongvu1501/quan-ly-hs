<?php

namespace App\Services\Finance;

use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Models\User;
use App\Support\Decimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollPaymentService
{
    public function __construct(private readonly FinanceAuditService $audit) {}

    /** @param array<string, mixed> $input */
    public function record(Payroll $payroll, array $input, User $actor): PayrollPayment
    {
        return DB::transaction(function () use ($payroll, $input, $actor): PayrollPayment {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            if ($locked->status !== 'locked') {
                throw ValidationException::withMessages(['payroll' => 'Chỉ payroll đã khóa mới được thanh toán.']);
            }
            $amount = Decimal::money($input['amount']);
            if (! Decimal::isPositive($amount)) {
                throw ValidationException::withMessages(['amount' => 'Số tiền phải lớn hơn 0.']);
            }
            if (Decimal::compare($amount, $locked->remaining_amount) > 0) {
                throw ValidationException::withMessages(['amount' => 'Số tiền vượt quá lương còn phải trả.']);
            }

            $payment = PayrollPayment::create([
                'payment_code' => 'PP-'.Str::upper((string) Str::ulid()),
                'payroll_id' => $locked->id,
                'teacher_id' => $locked->teacher_id,
                'amount' => $amount,
                'payment_date' => Carbon::parse($input['payment_date'], config('app.timezone')),
                'payment_method' => $input['payment_method'],
                'receipt_number' => $input['receipt_number'] ?? null,
                'status' => 'completed',
                'note' => $input['note'] ?? null,
                'created_by' => $actor->id,
            ]);
            $this->refreshPayrollTotals($locked);
            $this->audit->record($payment, 'payroll_payment.recorded', $actor, newValues: [
                'payroll_id' => $locked->id, 'amount' => $amount,
                'payment_method' => $payment->payment_method,
            ]);

            return $payment->fresh();
        });
    }

    public function cancel(PayrollPayment $payment, string $reason, User $actor): PayrollPayment
    {
        return DB::transaction(function () use ($payment, $reason, $actor): PayrollPayment {
            $lockedPayment = PayrollPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->status !== 'completed') {
                throw ValidationException::withMessages(['payment' => 'Giao dịch không còn hiệu lực.']);
            }
            $payroll = Payroll::query()->lockForUpdate()->findOrFail($lockedPayment->payroll_id);
            $lockedPayment->update([
                'status' => 'cancelled', 'reversed_by' => $actor->id,
                'reversed_at' => now(), 'reversal_reason' => $reason,
            ]);
            $this->refreshPayrollTotals($payroll);
            $this->audit->record($lockedPayment, 'payroll_payment.cancelled', $actor,
                ['status' => 'completed'], ['status' => 'cancelled', 'reversal_reason' => $reason]);

            return $lockedPayment->fresh();
        });
    }

    private function refreshPayrollTotals(Payroll $payroll): void
    {
        $paid = Decimal::money((string) $payroll->payments()->where('status', 'completed')->sum('amount'));
        $remaining = Decimal::subtract($payroll->net_salary, $paid);
        if (Decimal::isNegative($remaining)) {
            throw ValidationException::withMessages(['amount' => 'Tổng thanh toán vượt net salary.']);
        }
        $payroll->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => Decimal::compare($remaining, '0.00') === 0 ? 'paid' : 'locked',
        ]);
    }
}
