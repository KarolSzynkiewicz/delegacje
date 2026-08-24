<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()->after('assignee_id')->constrained('users')->nullOnDelete();
            $table->index(['created_by_id', 'status']);
        });

        DB::update('
            UPDATE work_items wi
            INNER JOIN project_tasks pt ON wi.source_type = ? AND wi.source_id = pt.id
            SET wi.created_by_id = pt.created_by
            WHERE wi.created_by_id IS NULL
        ', ['project_task']);

        DB::update('
            UPDATE work_items wi
            INNER JOIN task_subtasks ts ON wi.source_type = ? AND wi.source_id = ts.id
            LEFT JOIN project_tasks pt ON ts.task_id = pt.id
            SET wi.created_by_id = COALESCE(ts.created_by, pt.created_by)
            WHERE wi.created_by_id IS NULL
        ', ['task_subtask']);

        DB::update('
            UPDATE work_items wi
            INNER JOIN procedure_runs pr ON wi.source_type = ? AND wi.source_id = pr.id
            LEFT JOIN project_tasks pt ON pt.procedure_run_id = pr.id
            SET wi.created_by_id = COALESCE(pt.created_by, pr.started_by)
            WHERE wi.created_by_id IS NULL
        ', ['procedure_run']);

        DB::update('
            UPDATE work_items wi
            INNER JOIN comment_mentions cm ON wi.source_type = ? AND wi.source_id = cm.id
            SET wi.created_by_id = cm.created_by
            WHERE wi.created_by_id IS NULL
        ', ['comment_mention']);

        DB::update('
            UPDATE work_items wi
            INNER JOIN warehouse_dispatches wd ON wi.source_type = ? AND wi.source_id = wd.id
            SET wi.created_by_id = wd.created_by
            WHERE wi.created_by_id IS NULL
        ', ['warehouse_dispatch']);

        if (Schema::hasTable('approval_requests')) {
            DB::update('
                UPDATE work_items wi
                INNER JOIN approval_requests ar ON wi.source_type = ? AND wi.source_id = ar.id
                SET wi.created_by_id = ar.created_by
                WHERE wi.created_by_id IS NULL
            ', ['approval_request']);
        }
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropIndex(['created_by_id', 'status']);
            $table->dropConstrainedForeignId('created_by_id');
        });
    }
};
