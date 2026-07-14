<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_tasks_view', 20)->default('cards')->after('employee_id');
            $table->string('default_tasks_grid_view_slug')->nullable()->after('default_tasks_view');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['default_tasks_view', 'default_tasks_grid_view_slug']);
        });
    }
};
