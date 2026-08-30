<?php

namespace App\Services\Finance;

use App\Models\TeacherPayrollInput;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollInputService
{
    public function __construct(private readonly FinanceAuditService $audit) {}

    /** @param array<string, mixed> $input */
    public function save(array $input, User $actor, ?TeacherPayrollInput $payrollInput = null): TeacherPayrollInput
    {
        return DB::transaction(function () use ($input, $actor, $payrollInput): TeacherPayrollInput {
            if ($payrollInput && ($payrollInput->status !== 'draft' || $payrollInput->included_payroll_id !== null)) {
                throw ValidationException::withMessages(['payroll_input' => 'Input đã duyệt hoặc đã đưa vào payroll.']);
            }
            $manual = in_array($input['input_type'], ['bonus', 'manual_adjustment', 'other_input'], true);
            if ($manual && ($input['input_amount'] ?? null) === null) {
                throw ValidationException::withMessages(['input_amount' => 'Input thủ công yêu cầu số tiền.']);
            }
            if (! $manual && ($input['quantity'] ?? null) === null) {
                throw ValidationException::withMessages(['quantity' => 'Input theo đơn vị yêu cầu số lượng.']);
            }

            $values = [
                'teacher_id' => $input['teacher_id'],
                'academic_year_id' => $input['academic_year_id'] ?? null,
                'salary_component_id' => $input['salary_component_id'],
                'input_year' => $input['input_year'], 'input_month' => $input['input_month'],
                'input_type' => $input['input_type'], 'quantity' => $input['quantity'] ?? null,
                'input_amount' => $input['input_amount'] ?? null, 'status' => $input['status'],
                'note' => $input['note'] ?? null, 'created_by' => $payrollInput?->created_by ?? $actor->id,
                'approved_by' => $input['status'] === 'approved' ? $actor->id : null,
                'approved_at' => $input['status'] === 'approved' ? now() : null,
            ];
            if ($payrollInput) {
                $payrollInput->update($values);
            } else {
                $payrollInput = TeacherPayrollInput::create($values + [
                    'input_code' => 'PI-'.Str::upper((string) Str::ulid()),
                ]);
            }
            $this->audit->record($payrollInput, 'payroll_input.saved', $actor, newValues: [
                'input_code' => $payrollInput->input_code, 'input_type' => $payrollInput->input_type,
                'quantity' => $payrollInput->quantity, 'input_amount' => $payrollInput->input_amount,
                'status' => $payrollInput->status,
            ]);

            return $payrollInput->fresh();
        });
    }
}
