<?php

namespace Database\Seeders;

use Database\Seeders\Support\SchoolDemo;
use Illuminate\Database\Seeder;

class SchoolDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Đang làm mới dữ liệu trường học mẫu...');

        $this->call([
            SchoolDemoCleanupSeeder::class,
            SchoolStructureDemoSeeder::class,
            SchoolAcademicDemoSeeder::class,
            SchoolFinanceDemoSeeder::class,
        ]);

        $this->command?->newLine();
        $this->command?->info('Hoàn tất dữ liệu trường học mẫu.');
        $this->command?->line('Tài khoản mẫu dùng email @'.SchoolDemo::DEMO_EMAIL_DOMAIN);
        $this->command?->line('Mật khẩu chung: '.SchoolDemo::DEFAULT_PASSWORD);
        $this->command?->line('Dọn dữ liệu: php artisan db:seed --class=SchoolDemoCleanupSeeder');
    }
}
