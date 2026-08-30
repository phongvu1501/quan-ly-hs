<?php

namespace App\Services\Finance;

use App\Models\TaxConfig;
use App\Support\Decimal;
use Illuminate\Validation\ValidationException;

class TaxCalculationService
{
    /** @return array{taxable_income: string, tax_amount: string} */
    public function calculate(
        TaxConfig $config,
        string $taxableEarnings,
        string $insuranceDeduction,
        int $dependentCount
    ): array {
        $deductions = Decimal::add(
            Decimal::add($config->personal_deduction, Decimal::multiply($config->dependent_deduction, $dependentCount)),
            $insuranceDeduction
        );
        $taxableIncome = Decimal::max('0.00', Decimal::subtract($taxableEarnings, $deductions));
        if (! Decimal::isPositive($taxableIncome)) {
            return ['taxable_income' => '0.00', 'tax_amount' => '0.00'];
        }

        $matching = $config->brackets->filter(function ($bracket) use ($taxableIncome): bool {
            return Decimal::compare($taxableIncome, $bracket->lower_bound) >= 0
                && ($bracket->upper_bound === null || Decimal::compare($taxableIncome, $bracket->upper_bound) <= 0);
        })->values();
        if ($matching->count() !== 1) {
            throw ValidationException::withMessages([
                'tax_config' => "Cấu hình thuế {$config->code} không có đúng một bậc cho thu nhập tính thuế.",
            ]);
        }

        $bracket = $matching->first();
        $tax = Decimal::max('0.00', Decimal::subtract(
            Decimal::percentOf($taxableIncome, $bracket->rate_percentage),
            $bracket->quick_deduction
        ));

        return ['taxable_income' => $taxableIncome, 'tax_amount' => $tax];
    }
}
