<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('equipment_issues', 'project_assignment_id')) {
            return;
        }

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_assignment_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('equipment_issues', 'project_assignment_id')) {
            return;
        }

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->foreignId('project_assignment_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('project_assignments')
                ->nullOnDelete();
        });
    }
};
