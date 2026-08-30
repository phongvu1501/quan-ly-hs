<?php

namespace App\Services\Finance;

use App\Models\FeePolicy;
use App\Support\Decimal;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FeeCalculationService
{
    /**
     * @param  Collection<int, FeePolicy>  $matchingPolicies
     * @return array{original_amount: string, discount_amount: string, final_amount: string, discounts: list<array{policy: FeePolicy, calculated_discount: string}>}
     */
    public function calculate(string|int $originalAmount, Collection $matchingPolicies): array
    {
        $original = Decimal::money($originalAmount);
        if (Decimal::isNegative($original)) {
            throw ValidationException::withMessages(['amount' => 'Số tiền gốc không được âm.']);
        }

        $policies = $this->selectApplicablePolicies($matchingPolicies);
        $remaining = $original;
        $discounts = [];

        foreach ($policies as $policy) {
            $discount = match ($policy->reduction_type) {
                'percentage' => Decimal::percentOf($remaining, $policy->reduction_value),
                'fixed_amount' => Decimal::min($remaining, Decimal::money($policy->reduction_value)),
                'full_exemption' => $remaining,
                default => throw ValidationException::withMessages([
                    'fee_policy' => "Loại giảm [{$policy->reduction_type}] không được hỗ trợ.",
                ]),
            };
            $discount = Decimal::min($remaining, Decimal::max('0.00', $discount));
            $remaining = Decimal::subtract($remaining, $discount);
            $discounts[] = ['policy' => $policy, 'calculated_discount' => $discount];
        }

        return [
            'original_amount' => $original,
            'discount_amount' => Decimal::subtract($original, $remaining),
            'final_amount' => $remaining,
            'discounts' => $discounts,
        ];
    }

    /** @param Collection<int, FeePolicy> $policies @return Collection<int, FeePolicy> */
    private function selectApplicablePolicies(Collection $policies): Collection
    {
        if ($policies->isEmpty()) {
            return collect();
        }

        $invalid = $policies->first(
            fn (FeePolicy $policy) => ! in_array($policy->application_mode, FeePolicy::APPLICATION_MODES, true)
        );
        if ($invalid) {
            throw ValidationException::withMessages([
                'fee_policy' => "Policy {$invalid->code} có chế độ áp dụng không hợp lệ.",
            ]);
        }

        $exclusive = $policies->where('application_mode', 'exclusive')
            ->sortByDesc('priority')
            ->values();
        if ($exclusive->isNotEmpty()) {
            $highestPriority = (int) $exclusive->first()->priority;
            $winners = $exclusive->where('priority', $highestPriority)->values();
            if ($winners->count() !== 1) {
                throw ValidationException::withMessages([
                    'fee_policy' => 'Có nhiều policy exclusive cùng mức ưu tiên; không thể tự xác định policy thắng.',
                ]);
            }

            return $winners;
        }

        return $policies->where('application_mode', 'cumulative')
            ->sortByDesc('priority')
            ->values();
    }
}
