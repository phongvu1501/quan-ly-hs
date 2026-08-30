<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeAssessment;
use App\Models\FeeCategory;
use App\Models\FeeNotice;
use App\Models\FeePayment;
use App\Models\FeePolicy;
use App\Models\FeeRate;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentFeePolicy;
use App\Models\User;
use App\Services\Finance\FeePaymentService;
use App\Services\Finance\StudentFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinanceFeeBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_percentage_fixed_and_full_exemption_are_calculated_and_snapshotted(): void
    {
        $context = $this->feeContext();
        $service = app(StudentFeeService::class);

        $normal = $service->generate($this->generationInput($context, '2026-09', 1), $context['actor']);
        $normalAssessment = FeeAssessment::where('fee_notice_id', $normal['notice']->id)->firstOrFail();
        $this->assertSame('500000.00', $normalAssessment->original_amount);
        $this->assertSame('0.00', $normalAssessment->discount_amount);
        $this->assertSame('500000.00', $normalAssessment->final_amount);

        $this->assignPolicy($context, 'NEAR_POOR', 'percentage', '50', '2026-10-01');
        $percentage = $service->generate($this->generationInput($context, '2026-10', 1, '2026-10-01'), $context['actor']);
        $percentageAssessment = FeeAssessment::where('fee_notice_id', $percentage['notice']->id)->firstOrFail();
        $this->assertSame('250000.00', $percentageAssessment->discount_amount);
        $this->assertSame('250000.00', $percentageAssessment->final_amount);
        $this->assertDatabaseHas('fee_assessment_discounts', [
            'fee_assessment_id' => $percentageAssessment->id,
            'policy_code' => 'NEAR_POOR',
            'policy_name' => 'Policy NEAR_POOR',
            'calculated_discount' => '250000.00',
        ]);

        StudentFeePolicy::query()->update(['status' => 'inactive']);
        $this->assignPolicy($context, 'FIXED_SUPPORT', 'fixed_amount', '200000', '2026-11-01');
        $fixed = $service->generate($this->generationInput($context, '2026-11', 1, '2026-11-01'), $context['actor']);
        $fixedAssessment = FeeAssessment::where('fee_notice_id', $fixed['notice']->id)->firstOrFail();
        $this->assertSame('200000.00', $fixedAssessment->discount_amount);
        $this->assertSame('300000.00', $fixedAssessment->final_amount);

        StudentFeePolicy::query()->update(['status' => 'inactive']);
        $this->assignPolicy($context, 'FULL_EXEMPTION', 'full_exemption', '100', '2026-12-01');
        $exempt = $service->generate($this->generationInput($context, '2026-12', 1, '2026-12-01'), $context['actor']);
        $exemptAssessment = FeeAssessment::where('fee_notice_id', $exempt['notice']->id)->firstOrFail();
        $this->assertSame('0.00', $exemptAssessment->final_amount);
        $this->assertSame('paid', $exemptAssessment->status);
    }

    public function test_fee_generation_is_idempotent_and_ambiguous_exclusive_policies_fail(): void
    {
        $context = $this->feeContext();
        $input = $this->generationInput($context, '2026-09', 1);
        $service = app(StudentFeeService::class);

        $first = $service->generate($input, $context['actor']);
        $second = $service->generate($input, $context['actor']);

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['skipped']);
        $this->assertDatabaseCount('fee_assessments', 1);

        $context = $this->feeContext('2027-2028');
        $this->assignPolicy($context, 'POLICY_A', 'percentage', '30', '2027-09-01', 10);
        $this->assignPolicy($context, 'POLICY_B', 'percentage', '50', '2027-09-01', 10);
        $this->expectException(ValidationException::class);
        $service->generate($this->generationInput($context, '2027-09', 1, '2027-09-01'), $context['actor']);
    }

    public function test_multiple_partial_payments_overpayment_rejection_cancellation_and_remaining_calculation(): void
    {
        $context = $this->feeContext();
        $generated = app(StudentFeeService::class)->generate(
            $this->generationInput($context, '2026-09', 1),
            $context['actor']
        );
        $assessment = FeeAssessment::where('fee_notice_id', $generated['notice']->id)->firstOrFail();
        $payments = app(FeePaymentService::class);

        $first = $payments->record($assessment, [
            'amount' => '200000', 'payment_date' => '2026-09-10 08:00:00', 'payment_method' => 'cash',
        ], $context['actor']);
        $assessment->refresh();
        $this->assertSame('partially_paid', $assessment->status);
        $this->assertSame('200000.00', $assessment->paid_amount);
        $this->assertSame('300000.00', $assessment->remaining_amount);

        $second = $payments->record($assessment, [
            'amount' => '300000', 'payment_date' => '2026-09-12 09:00:00', 'payment_method' => 'bank_transfer',
        ], $context['actor']);
        $assessment->refresh();
        $this->assertSame('paid', $assessment->status);
        $this->assertSame('0.00', $assessment->remaining_amount);
        $this->assertSame(2, $assessment->payments()->where('status', 'completed')->count());

        $payments->cancel($second, 'Chuyển khoản bị hoàn', $context['actor']);
        $assessment->refresh();
        $this->assertSame('partially_paid', $assessment->status);
        $this->assertSame('300000.00', $assessment->remaining_amount);

        try {
            $payments->record($assessment, [
                'amount' => '300000.01', 'payment_date' => '2026-09-13 09:00:00', 'payment_method' => 'cash',
            ], $context['actor']);
            $this->fail('Expected overpayment validation error.');
        } catch (ValidationException) {
            $this->assertSame(1, FeePayment::where('fee_assessment_id', $assessment->id)->where('status', 'completed')->count());
        }

        $this->assertSame('completed', $first->fresh()->status);
    }

    public function test_policy_changes_do_not_change_historical_assessment_and_legacy_payments_are_isolated(): void
    {
        $context = $this->feeContext();
        $policy = $this->assignPolicy($context, 'HISTORICAL', 'percentage', '50', '2026-09-01');
        $result = app(StudentFeeService::class)->generate(
            $this->generationInput($context, '2026-09', 1),
            $context['actor']
        );
        $assessment = FeeAssessment::where('fee_notice_id', $result['notice']->id)->firstOrFail();

        $policy->update(['name' => 'Renamed', 'reduction_value' => '10']);
        $assessment->refresh()->load('discounts');
        $this->assertSame('250000.00', $assessment->final_amount);
        $this->assertSame('Policy HISTORICAL', $assessment->discounts->first()->policy_name);
        $this->assertSame('50.0000', $assessment->discounts->first()->reduction_value);

        $legacyNotice = FeeNotice::create([
            'academic_year_id' => $context['year']->id,
            'semester_id' => $context['semester']->id,
            'title' => 'Legacy notice', 'amount' => '2000000', 'is_active' => true,
        ]);
        $legacy = FeePayment::create([
            'fee_notice_id' => $legacyNotice->id, 'fee_assessment_id' => null,
            'student_id' => $context['student']->id, 'amount' => '1000000',
            'status' => 'paid', 'paid_at' => '2026-08-21 19:56:00',
        ]);

        $this->assertTrue($legacy->isLegacy());
        $this->assertSame('250000.00', (string) FeeAssessment::sum('final_amount'));
        $this->assertDatabaseCount('fee_assessments', 1);
        $this->assertDatabaseHas('fee_payments', [
            'id' => $legacy->id, 'fee_assessment_id' => null, 'amount' => '1000000.00',
            'status' => 'paid', 'paid_at' => '2026-08-21 19:56:00',
        ]);
    }

    /** @return array<string, mixed> */
    private function feeContext(string $yearCode = '2026-2027'): array
    {
        $start = (int) substr($yearCode, 0, 4);
        $year = AcademicYear::create([
            'name' => $yearCode, 'code' => $yearCode,
            'starts_on' => "{$start}-08-01", 'ends_on' => ($start + 1).'-05-31', 'is_current' => true,
        ]);
        $semester = Semester::create([
            'academic_year_id' => $year->id, 'number' => 1, 'name' => 'Học kỳ 1',
            'starts_on' => "{$start}-08-01", 'ends_on' => "{$start}-12-31", 'is_current' => true,
        ]);
        $grade = Grade::firstOrCreate(['level' => 10], ['name' => 'Khối 10']);
        $class = SchoolClass::create([
            'academic_year_id' => $year->id, 'grade_id' => $grade->id,
            'code' => '10A1-'.$start, 'name' => '10A1', 'status' => 'active',
        ]);
        $student = Student::create(['full_name' => 'Student Fee', 'status' => 'studying']);
        $student->enrollments()->create([
            'academic_year_id' => $year->id, 'class_id' => $class->id, 'status' => 'active',
        ]);
        $category = FeeCategory::create(['code' => 'TUITION-'.$start, 'name' => 'Học phí', 'type' => 'tuition']);
        $rate = FeeRate::create([
            'fee_category_id' => $category->id, 'academic_year_id' => $year->id,
            'code' => 'RATE-'.$start, 'name' => 'Học phí chuẩn', 'period_type' => 'monthly',
            'amount' => '500000', 'effective_from' => "{$start}-08-01", 'is_active' => true,
        ]);

        return compact('year', 'semester', 'class', 'student', 'category', 'rate')
            + ['actor' => User::factory()->create()];
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function generationInput(array $context, string $periodKey, int $sequence, ?string $date = null): array
    {
        return [
            'academic_year_id' => $context['year']->id, 'semester_id' => $context['semester']->id,
            'fee_category_id' => $context['category']->id, 'fee_rate_id' => $context['rate']->id,
            'title' => 'Học phí '.$periodKey, 'period_type' => 'monthly', 'period_key' => $periodKey,
            'charge_sequence' => $sequence, 'assessment_date' => $date ?? $periodKey.'-01',
            'due_date' => ($date ?? $periodKey.'-01'), 'scope_type' => 'all',
        ];
    }

    /** @param array<string, mixed> $context */
    private function assignPolicy(
        array $context,
        string $code,
        string $type,
        string $value,
        string $from,
        int $priority = 10
    ): FeePolicy {
        $policy = FeePolicy::create([
            'academic_year_id' => $context['year']->id, 'fee_category_id' => $context['category']->id,
            'code' => $code, 'name' => 'Policy '.$code, 'reduction_type' => $type,
            'reduction_value' => $value, 'application_mode' => 'exclusive', 'priority' => $priority,
            'effective_from' => $from, 'status' => 'active',
        ]);
        StudentFeePolicy::create([
            'student_id' => $context['student']->id, 'fee_policy_id' => $policy->id,
            'effective_from' => $from, 'status' => 'active',
        ]);

        return $policy;
    }
}
