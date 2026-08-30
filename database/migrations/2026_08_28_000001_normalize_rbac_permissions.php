<?php

use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionSeeder::class)->run();
    }

    public function down(): void
    {
        // Permission and role mappings are live authorization data.
        // A rollback must not remove or broaden them implicitly.
    }
};
