<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->string('created_by_filter', 30)->default('')->after('assigned_filter');
        });
    }

    public function down(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->dropColumn('created_by_filter');
        });
    }
};
