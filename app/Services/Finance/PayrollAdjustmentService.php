<?php

namespace App\Services\Finance;

use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollAdjustmentService
{
    public function __construct(private readonly FinanceAuditService $audit) {}

    public function create(
        Payroll $payroll,
        SalaryComponent $component,
        string $amount,
        string $reason,
        User $actor
    ): PayrollAdjustment {
        return DB::transaction(function () use ($payroll, $component, $amount, $reason, $actor): PayrollAdjustment {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            if (! in_array($locked->status, ['locked', 'paid'], true)) {
                throw ValidationException::withMessages([
                    'payroll' => 'Adjustment chỉ dùng cho payroll đã khóa hoặc đã thanh toán.',
                ]);
            }
            $normalized = Decimal::money($amount);
            if (! Decimal::isPositive($normalized)) {
                throw ValidationException::withMessages(['amount' => 'Số tiền adjustment phải lớn hơn 0.']);
            }
            $adjustment = PayrollAdjustment::create([
                'adjustment_code' => 'PA-'.Str::upper((string) Str::ulid()),
                'payroll_id' => $locked->id,
                'teacher_id' => $locked->teacher_id,
                'salary_component_id' => $component->id,
                'component_code_snapshot' => $component->code,
                'component_name_snapshot' => $component->name,
                'type' => $component->type,
                'amount' => $normalized,
                'reason' => $reason,
                'status' => 'draft',
            ]);
            $this->audit->record($adjustment, 'payroll_adjustment.created', $actor, newValues: [
                'payroll_id' => $locked->id, 'type' => $component->type,
                'amount' => $normalized, 'reason' => $reason,
            ]);

            return $adjustment;
        });
    }

    public function approve(PayrollAdjustment $adjustment, User $actor): PayrollAdjustment
    {
        return $this->transition($adjustment, 'draft', 'approved', 'payroll_adjustment.approved', $actor, [
            'approved_by' => $actor->id, 'approved_at' => now(),
        ]);
    }

    public function lock(PayrollAdjustment $adjustment, User $actor): PayrollAdjustment
    {
        return $this->transition($adjustment, 'approved', 'locked', 'payroll_adjustment.locked', $actor, [
            'locked_by' => $actor->id, 'locked_at' => now(),
        ]);
    }

    public function settle(
        PayrollAdjustment $adjustment,
        string $method,
        ?string $reference,
        User $actor
    ): PayrollAdjustment {
        return $this->transition($adjustment, 'locked', 'paid', 'payroll_adjustment.paid', $actor, [
            'settled_by' => $actor->id, 'settled_at' => now(),
            'settlement_method' => $method, 'settlement_reference' => $reference,
        ]);
    }

    public function cancel(PayrollAdjustment $adjustment, string $reason, User $actor): PayrollAdjustment
    {
        return DB::transaction(function () use ($adjustment, $reason, $actor): PayrollAdjustment {
            $locked = PayrollAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if (in_array($locked->status, ['paid', 'cancelled'], true)) {
                throw ValidationException::withMessages(['adjustment' => 'Adjustment đã thanh toán hoặc đã hủy.']);
            }
            $old = $locked->status;
            $locked->update([
                'status' => 'cancelled', 'cancelled_by' => $actor->id,
                'cancelled_at' => now(), 'cancellation_reason' => $reason,
            ]);
            $this->audit->record($locked, 'payroll_adjustment.cancelled', $actor,
                ['status' => $old], ['status' => 'cancelled', 'reason' => $reason]);

            return $locked->fresh();
        });
    }

    /** @param array<string, mixed> $extra */
    private function transition(
        PayrollAdjustment $adjustment,
        string $from,
        string $to,
        string $action,
        User $actor,
        array $extra
    ): PayrollAdjustment {
        return DB::transaction(function () use ($adjustment, $from, $to, $action, $actor, $extra): PayrollAdjustment {
            $locked = PayrollAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if ($locked->status !== $from) {
                throw ValidationException::withMessages([
                    'adjustment' => "Adjustment phải ở trạng thái {$from}.",
                ]);
            }
            $locked->update(['status' => $to] + $extra);
            $this->audit->record($locked, $action, $actor, ['status' => $from], ['status' => $to]);

            return $locked->fresh();
        });
    }
}
