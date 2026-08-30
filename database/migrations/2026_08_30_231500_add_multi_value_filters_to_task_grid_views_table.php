<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->json('status_filter')->nullable()->after('status');
            $table->json('assigned_filters')->nullable()->after('assigned_filter');
            $table->json('created_by_filters')->nullable()->after('created_by_filter');
        });
    }

    public function down(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->dropColumn(['status_filter', 'assigned_filters', 'created_by_filters']);
        });
    }
};
