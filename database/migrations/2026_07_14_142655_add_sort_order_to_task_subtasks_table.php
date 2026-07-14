<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_subtasks', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('task_id');
        });

        // Seed sort_order based on existing created_at order within each task
        DB::statement('
            UPDATE task_subtasks ts
            JOIN (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY task_id ORDER BY created_at, id) AS rn
                FROM task_subtasks
            ) ranked ON ts.id = ranked.id
            SET ts.sort_order = ranked.rn
        ');
    }

    public function down(): void
    {
        Schema::table('task_subtasks', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
