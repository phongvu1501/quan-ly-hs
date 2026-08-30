<?php

namespace App\Services\Finance;

use App\Models\InsuranceConfig;
use App\Support\Decimal;

class InsuranceCalculationService
{
    /** @return array{salary_base: string, employee_amount: string} */
    public function calculate(InsuranceConfig $config, string $resolvedSalaryBase): array
    {
        $base = Decimal::max('0.00', $resolvedSalaryBase);
        if ($config->maximum_salary_base !== null) {
            $base = Decimal::min($base, $config->maximum_salary_base);
        }

        return [
            'salary_base' => $base,
            'employee_amount' => Decimal::percentOf($base, $config->employee_rate_percentage),
        ];
    }
}
