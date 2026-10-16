<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_run_steps', function (Blueprint $table) {
            $table->index(
                ['node_type', 'completed_at', 'resume_at'],
                'procedure_run_steps_type_completed_resume_index'
            );
            $table->index(
                ['procedure_run_id', 'node_type', 'completed_at'],
                'procedure_run_steps_run_type_open_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('procedure_run_steps', function (Blueprint $table) {
            $table->dropIndex('procedure_run_steps_type_completed_resume_index');
            $table->dropIndex('procedure_run_steps_run_type_open_index');
        });
    }
};
