<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->string('filter_join', 8)->default('and')->after('type_filter');
            $table->json('filter_ops')->nullable()->after('filter_join');
        });
    }

    public function down(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->dropColumn(['filter_join', 'filter_ops']);
        });
    }
};
