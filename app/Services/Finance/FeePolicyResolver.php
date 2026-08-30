<?php

namespace App\Services\Finance;

use App\Models\FeeCategory;
use App\Models\FeePolicy;
use App\Models\Student;
use App\Models\StudentFeePolicy;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FeePolicyResolver
{
    /** @return Collection<int, FeePolicy> */
    public function resolve(
        Student $student,
        FeeCategory $category,
        int $academicYearId,
        CarbonInterface $assessmentDate
    ): Collection {
        $date = $assessmentDate->toDateString();

        return StudentFeePolicy::query()
            ->with('policy')
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($query) => $query
                ->whereNull('effective_to')
                ->orWhereDate('effective_to', '>=', $date))
            ->whereHas('policy', fn ($query) => $query
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->where(fn ($query) => $query
                    ->whereNull('fee_category_id')
                    ->orWhere('fee_category_id', $category->id))
                ->whereDate('effective_from', '<=', $date)
                ->where(fn ($query) => $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date)))
            ->get()
            ->pluck('policy')
            ->filter()
            ->unique('id')
            ->values();
    }
}
