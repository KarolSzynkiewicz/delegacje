<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->string('search_task', 255)->default('')->after('sort_direction');
            $table->string('search_project', 255)->default('')->after('search_task');
            $table->string('search_category', 255)->default('')->after('search_project');
            $table->string('search_assigned_to', 255)->default('')->after('search_category');
            $table->string('status', 20)->default('')->after('search_assigned_to');
            $table->boolean('my_tasks_only')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->dropColumn([
                'search_task',
                'search_project',
                'search_category',
                'search_assigned_to',
                'status',
                'my_tasks_only',
            ]);
        });
    }
};
