<?php

namespace App\Services\Finance;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeAssessment;
use App\Models\FeeCategory;
use App\Models\FeeNotice;
use App\Models\FeePolicy;
use App\Models\FeeRate;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentFeePolicy;
use App\Models\User;
use App\Support\Decimal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentFeeService
{
    public function __construct(
        private readonly FeeCalculationService $calculator,
        private readonly FinanceAuditService $audit
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{notice: FeeNotice, created: int, skipped: int}
     */
    public function generate(array $input, User $actor): array
    {
        $context = $this->validateContext($input);
        $generationKey = $this->generationKey($input, $context['category'], $context['rate']);

        $notice = FeeNotice::query()->createOrFirst(
            ['generation_key' => $generationKey],
            [
                'notice_code' => 'FN-'.Str::upper((string) Str::ulid()),
                'academic_year_id' => $context['year']->id,
                'semester_id' => $context['semester']?->id,
                'class_id' => $context['class']?->id,
                'fee_category_id' => $context['category']->id,
                'fee_rate_id' => $context['rate']->id,
                'title' => $input['title'],
                'amount' => $context['rate']->amount,
                'period_type' => $input['period_type'],
                'period_key' => $input['period_key'],
                'charge_sequence' => (int) $input['charge_sequence'],
                'assessment_date' => $context['assessment_date']->toDateString(),
                'scope_type' => $input['scope_type'],
                'due_on' => $input['due_date'] ?? null,
                'description' => $input['note'] ?? null,
                'is_active' => true,
                'status' => 'generating',
                'generated_by' => $actor->id,
            ]
        );

        $created = 0;
        $skipped = 0;
        $query = Enrollment::query()
            ->with(['student', 'class'])
            ->where('academic_year_id', $context['year']->id)
            ->where('status', 'active')
            ->whereHas('student', fn ($query) => $query->where('status', Student::STATUS_STUDYING))
            ->when($input['scope_type'] === 'class', fn ($query) => $query->where('class_id', $context['class']->id))
            ->when($input['scope_type'] === 'selected', fn ($query) => $query->whereIn('student_id', $input['student_ids']));

        $query->chunkById(200, function (EloquentCollection $enrollments) use (
            $notice, $context, $input, $actor, &$created, &$skipped
        ): void {
            DB::transaction(function () use (
                $enrollments, $notice, $context, $input, $actor, &$created, &$skipped
            ): void {
                $policiesByStudent = $this->policiesForStudents(
                    $enrollments->pluck('student_id'),
                    $context['category'],
                    $context['year']->id,
                    $context['assessment_date']
                );

                foreach ($enrollments as $enrollment) {
                    $calculation = $this->calculator->calculate(
                        $context['rate']->amount,
                        $policiesByStudent->get($enrollment->student_id, collect())
                    );
                    $businessKey = [
                        'student_id' => $enrollment->student_id,
                        'academic_year_id' => $context['year']->id,
                        'fee_category_id' => $context['category']->id,
                        'period_key' => $input['period_key'],
                        'charge_sequence' => (int) $input['charge_sequence'],
                    ];
                    $assessment = FeeAssessment::query()->createOrFirst($businessKey, [
                        'assessment_code' => 'FA-'.Str::upper((string) Str::ulid()),
                        'fee_notice_id' => $notice->id,
                        'semester_id' => $context['semester']?->id,
                        'class_id' => $enrollment->class_id,
                        'period_type' => $input['period_type'],
                        'assessment_date' => $context['assessment_date']->toDateString(),
                        'due_date' => $input['due_date'] ?? null,
                        'student_code_snapshot' => $enrollment->student->student_code,
                        'student_name_snapshot' => $enrollment->student->full_name,
                        'class_code_snapshot' => $enrollment->class?->code,
                        'class_name_snapshot' => $enrollment->class?->name,
                        'fee_category_code_snapshot' => $context['category']->code,
                        'fee_category_name_snapshot' => $context['category']->name,
                        'fee_notice_title_snapshot' => $notice->title,
                        'original_amount' => $calculation['original_amount'],
                        'discount_amount' => $calculation['discount_amount'],
                        'final_amount' => $calculation['final_amount'],
                        'paid_amount' => '0.00',
                        'remaining_amount' => $calculation['final_amount'],
                        'status' => Decimal::compare($calculation['final_amount'], '0.00') === 0 ? 'paid' : 'pending',
                        'note' => $input['note'] ?? null,
                        'generated_by' => $actor->id,
                        'locked_by' => $actor->id,
                        'locked_at' => now(),
                    ]);

                    if (! $assessment->wasRecentlyCreated) {
                        $skipped++;

                        continue;
                    }

                    foreach ($calculation['discounts'] as $sequence => $discount) {
                        $policy = $discount['policy'];
                        $assessment->discounts()->create([
                            'fee_policy_id' => $policy->id,
                            'sequence' => $sequence + 1,
                            'policy_code' => $policy->code,
                            'policy_name' => $policy->name,
                            'reduction_type' => $policy->reduction_type,
                            'reduction_value' => $policy->reduction_value,
                            'application_mode' => $policy->application_mode,
                            'priority' => $policy->priority,
                            'policy_effective_from' => $policy->effective_from,
                            'policy_effective_to' => $policy->effective_to,
                            'calculated_discount' => $discount['calculated_discount'],
                        ]);
                    }
                    $created++;
                }
            });
        });

        $notice->update(['status' => 'generated', 'generated_at' => now()]);
        $this->audit->record($notice, 'fees.generated', $actor, metadata: [
            'created' => $created,
            'skipped' => $skipped,
            'generation_key' => $generationKey,
        ]);

        return ['notice' => $notice->fresh(), 'created' => $created, 'skipped' => $skipped];
    }

    public function cancelAssessment(FeeAssessment $assessment, string $reason, User $actor): FeeAssessment
    {
        return DB::transaction(function () use ($assessment, $reason, $actor): FeeAssessment {
            $locked = FeeAssessment::query()->lockForUpdate()->findOrFail($assessment->id);
            if ($locked->status === 'cancelled') {
                return $locked;
            }
            if ($locked->payments()->where('status', 'completed')->exists()) {
                throw ValidationException::withMessages([
                    'assessment' => 'Không thể hủy khoản phải thu đã có thanh toán hợp lệ.',
                ]);
            }

            $old = $locked->only(['status', 'remaining_amount']);
            $locked->update([
                'status' => 'cancelled', 'cancelled_by' => $actor->id,
                'cancelled_at' => now(), 'cancellation_reason' => $reason,
            ]);
            $this->audit->record($locked, 'fee_assessment.cancelled', $actor, $old, $locked->only([
                'status', 'remaining_amount', 'cancellation_reason',
            ]));

            return $locked->fresh();
        });
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function validateContext(array $input): array
    {
        $year = AcademicYear::findOrFail($input['academic_year_id']);
        $category = FeeCategory::whereKey($input['fee_category_id'])->where('is_active', true)->firstOrFail();
        $rate = FeeRate::whereKey($input['fee_rate_id'])->where('is_active', true)->firstOrFail();
        $assessmentDate = Carbon::parse($input['assessment_date'], config('app.timezone'))->startOfDay();

        if ($rate->fee_category_id !== $category->id
            || $rate->academic_year_id !== $year->id
            || $rate->period_type !== $input['period_type']
            || $assessmentDate->lt($rate->effective_from)
            || ($rate->effective_to && $assessmentDate->gt($rate->effective_to))) {
            throw ValidationException::withMessages(['fee_rate_id' => 'Mức thu không phù hợp với kỳ phát sinh.']);
        }

        $semester = isset($input['semester_id'])
            ? Semester::whereKey($input['semester_id'])->where('academic_year_id', $year->id)->firstOrFail()
            : null;
        $class = isset($input['class_id'])
            ? SchoolClass::whereKey($input['class_id'])->where('academic_year_id', $year->id)->firstOrFail()
            : null;
        if ($input['scope_type'] === 'class' && ! $class) {
            throw ValidationException::withMessages(['class_id' => 'Phạm vi lớp yêu cầu một lớp hợp lệ.']);
        }
        if ($input['scope_type'] === 'selected' && empty($input['student_ids'])) {
            throw ValidationException::withMessages(['student_ids' => 'Hãy chọn ít nhất một học sinh.']);
        }

        return [
            'year' => $year,
            'category' => $category,
            'rate' => $rate,
            'assessment_date' => $assessmentDate,
            'semester' => $semester,
            'class' => $class,
        ];
    }

    /** @param array<string, mixed> $input */
    private function generationKey(array $input, FeeCategory $category, FeeRate $rate): string
    {
        $studentIds = collect($input['student_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();

        return hash('sha256', json_encode([
            'academic_year_id' => (int) $input['academic_year_id'],
            'semester_id' => isset($input['semester_id']) ? (int) $input['semester_id'] : null,
            'fee_category_id' => $category->id,
            'fee_rate_id' => $rate->id,
            'period_type' => $input['period_type'],
            'period_key' => $input['period_key'],
            'charge_sequence' => (int) $input['charge_sequence'],
            'scope_type' => $input['scope_type'],
            'class_id' => isset($input['class_id']) ? (int) $input['class_id'] : null,
            'student_ids' => $studentIds,
        ], JSON_THROW_ON_ERROR));
    }

    /** @return Collection<int, Collection<int, FeePolicy>> */
    private function policiesForStudents(
        Collection $studentIds,
        FeeCategory $category,
        int $academicYearId,
        Carbon $assessmentDate
    ): Collection {
        $date = $assessmentDate->toDateString();

        return StudentFeePolicy::query()
            ->with('policy')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->whereHas('policy', fn ($query) => $query
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('fee_category_id')->orWhere('fee_category_id', $category->id))
                ->whereDate('effective_from', '<=', $date)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date)))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($assignments) => $assignments->pluck('policy')->filter()->unique('id')->values());
    }
}
