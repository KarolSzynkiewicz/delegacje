<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_run_steps', function (Blueprint $table) {
            $table->timestamp('resume_at')->nullable()->after('completed_at');
        });

        Schema::table('approval_requests', function (Blueprint $table) {
            $table->foreignId('procedure_run_id')->nullable()->after('comment_id')
                ->constrained('procedure_runs')->nullOnDelete();
        });

        Schema::table('procedure_run_steps', function (Blueprint $table) {
            $table->foreignId('approval_request_id')->nullable()->after('resume_at')
                ->constrained('approval_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procedure_run_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_request_id');
            $table->dropColumn('resume_at');
        });

        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procedure_run_id');
        });
    }
};
