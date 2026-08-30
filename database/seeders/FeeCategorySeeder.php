<?php

namespace Database\Seeders;

use App\Models\FeeCategory;
use Illuminate\Database\Seeder;

class FeeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'TUITION', 'name' => 'Học phí', 'type' => 'tuition'],
            ['code' => 'EXTRA_CLASS', 'name' => 'Phí học thêm', 'type' => 'service'],
            ['code' => 'BOARDING', 'name' => 'Phí bán trú', 'type' => 'service'],
            ['code' => 'UNIFORM', 'name' => 'Phí đồng phục', 'type' => 'product'],
            ['code' => 'MATERIAL', 'name' => 'Phí tài liệu', 'type' => 'product'],
            ['code' => 'INSURANCE', 'name' => 'Phí bảo hiểm', 'type' => 'insurance'],
            ['code' => 'OTHER', 'name' => 'Phí khác', 'type' => 'other'],
        ];

        foreach ($categories as $category) {
            FeeCategory::firstOrCreate(['code' => $category['code']], $category + ['is_active' => true]);
        }
    }
}
