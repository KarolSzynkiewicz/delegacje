<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a follow-up task (e.g. "oddzwonić jutro") created from the recruitment pipeline
     * link back to the process it belongs to, so it shows up both in "Moje zadania" and in
     * the candidate's drawer.
     */
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreignId('recruitment_process_id')->nullable()->after('procedure_run_id')
                ->constrained('recruitment_processes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropForeign(['recruitment_process_id']);
            $table->dropColumn('recruitment_process_id');
        });
    }
};
