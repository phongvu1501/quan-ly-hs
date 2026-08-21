<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('menus', 'required_permission')) {
            Schema::table('menus', function (Blueprint $table): void {
                $table->string('required_permission')->nullable()->after('icon')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('menus', 'required_permission')) {
            Schema::table('menus', function (Blueprint $table): void {
                $table->dropColumn('required_permission');
            });
        }
    }
};
