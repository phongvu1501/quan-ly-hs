<?php

namespace App\Services\Finance;

use App\Models\AcademicYear;
use App\Models\Payroll;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(
        private readonly PayrollCalculationService $calculator,
        private readonly FinanceAuditService $audit
    ) {}

    /**
     * @param  list<int>  $teacherIds
     * @return array{created: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function generate(
        int $year,
        int $month,
        ?AcademicYear $academicYear,
        array $teacherIds,
        User $actor
    ): array {
        $created = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $query = Teacher::query()
            ->when($teacherIds !== [], fn ($query) => $query->whereIn('id', $teacherIds))
            ->when($teacherIds === [], fn ($query) => $query->where('status', 'active'));

        $query->select(['id', 'teacher_code', 'full_name', 'specialization', 'status'])
            ->chunkById(100, function ($teachers) use (
                $year, $month, $academicYear, $actor, &$created, &$skipped, &$failed, &$errors
            ): void {
                foreach ($teachers as $teacher) {
                    if (Payroll::where('teacher_id', $teacher->id)
                        ->where('payroll_year', $year)->where('payroll_month', $month)->exists()) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $this->calculator->calculate($teacher, $year, $month, $academicYear, $actor);
                        $created++;
                    } catch (ValidationException $exception) {
                        $failed++;
                        $errors[$teacher->id] = collect($exception->errors())->flatten()->first()
                            ?? 'Không thể tạo payroll.';
                    }
                }
            });

        return compact('created', 'skipped', 'failed', 'errors');
    }

    public function approve(Payroll $payroll, User $actor): Payroll
    {
        return $this->transition($payroll, 'calculated', 'approved', 'payroll.approved', $actor, [
            'approved_by' => $actor->id, 'approved_at' => now(),
        ]);
    }

    public function lock(Payroll $payroll, User $actor): Payroll
    {
        return $this->transition($payroll, 'approved', 'locked', 'payroll.locked', $actor, [
            'locked_by' => $actor->id, 'locked_at' => now(),
        ]);
    }

    public function cancel(Payroll $payroll, string $reason, User $actor): Payroll
    {
        return DB::transaction(function () use ($payroll, $reason, $actor): Payroll {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            if (in_array($locked->status, ['paid', 'cancelled'], true)
                || $locked->payments()->where('status', 'completed')->exists()) {
                throw ValidationException::withMessages(['payroll' => 'Payroll đã thanh toán hoặc đã hủy.']);
            }
            $oldStatus = $locked->status;
            $locked->update([
                'status' => 'cancelled', 'cancelled_by' => $actor->id,
                'cancelled_at' => now(), 'cancellation_reason' => $reason,
            ]);
            $this->audit->record($locked, 'payroll.cancelled', $actor, ['status' => $oldStatus], [
                'status' => 'cancelled', 'cancellation_reason' => $reason,
            ]);

            return $locked->fresh();
        });
    }

    /** @param array<string, mixed> $extra */
    private function transition(
        Payroll $payroll,
        string $from,
        string $to,
        string $action,
        User $actor,
        array $extra
    ): Payroll {
        return DB::transaction(function () use ($payroll, $from, $to, $action, $actor, $extra): Payroll {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            if ($locked->status !== $from) {
                throw ValidationException::withMessages([
                    'payroll' => "Payroll phải ở trạng thái {$from} để chuyển sang {$to}.",
                ]);
            }
            $locked->update(['status' => $to] + $extra);
            $this->audit->record($locked, $action, $actor, ['status' => $from], ['status' => $to]);

            return $locked->fresh();
        });
    }
}
