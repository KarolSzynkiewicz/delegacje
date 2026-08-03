<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employment lifecycle lives on Employee, not on RecruitmentProcess: terminating
     * someone must never rewrite recruitment history. A former employee is simply an
     * employee with terminated_at set; the linked candidate reflects this by reading
     * through the employee, not by storing a duplicate flag.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->timestamp('terminated_at')->nullable()->after('outside_base');
            $table->string('termination_reason', 30)->nullable()->after('terminated_at');
            $table->text('termination_note')->nullable()->after('termination_reason');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['terminated_at', 'termination_reason', 'termination_note']);
        });
    }
};
