<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Source of truth for "is this candidate currently an employee": a direct FK,
     * not a derived read of RecruitmentProcess status (which stays historical/audit-only).
     * Unique because one employee maps to at most one candidate identity.
     */
    public function up(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->unique()->after('id')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
