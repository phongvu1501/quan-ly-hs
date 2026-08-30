<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['code' => 'BASE_SALARY', 'name' => 'Lương cơ bản', 'type' => 'earning', 'category' => 'base_salary', 'calculation_type' => 'fixed', 'is_taxable' => true, 'is_insurance_base' => true],
            ['code' => 'ALLOWANCE', 'name' => 'Phụ cấp', 'type' => 'earning', 'category' => 'allowance', 'calculation_type' => 'fixed', 'is_taxable' => true, 'is_insurance_base' => false],
            ['code' => 'EXTRA_TEACHING', 'name' => 'Dạy thêm', 'type' => 'earning', 'category' => 'teaching_extra', 'calculation_type' => 'per_unit', 'is_taxable' => true, 'is_insurance_base' => false],
            ['code' => 'BONUS', 'name' => 'Thưởng', 'type' => 'earning', 'category' => 'bonus', 'calculation_type' => 'manual', 'is_taxable' => true, 'is_insurance_base' => false],
            ['code' => 'ADVANCE', 'name' => 'Tạm ứng', 'type' => 'deduction', 'category' => 'advance', 'calculation_type' => 'manual', 'is_taxable' => false, 'is_insurance_base' => false],
            ['code' => 'UNPAID_LEAVE', 'name' => 'Nghỉ không lương', 'type' => 'deduction', 'category' => 'unpaid_leave', 'calculation_type' => 'per_unit', 'is_taxable' => false, 'is_insurance_base' => false],
            ['code' => 'OTHER_DEDUCTION', 'name' => 'Khấu trừ khác', 'type' => 'deduction', 'category' => 'other_deduction', 'calculation_type' => 'manual', 'is_taxable' => false, 'is_insurance_base' => false],
        ];

        foreach ($components as $component) {
            SalaryComponent::firstOrCreate(['code' => $component['code']], $component + ['is_active' => true]);
        }
    }
}
