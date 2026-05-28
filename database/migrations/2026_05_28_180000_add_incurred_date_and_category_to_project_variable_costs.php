<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_variable_costs', function (Blueprint $table) {
            if (!Schema::hasColumn('project_variable_costs', 'incurred_date')) {
                $table->date('incurred_date')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('project_variable_costs', 'category')) {
                $table->string('category', 64)->nullable()->after('incurred_date');
            }
        });

        // Backfill incurred_date z created_at dla istniejących rekordów
        DB::statement(
            'UPDATE project_variable_costs SET incurred_date = DATE(created_at) WHERE incurred_date IS NULL'
        );

        // Po backfillu wymuszamy NOT NULL — wszystkie nowe wpisy muszą mieć datę poniesienia
        if (Schema::hasColumn('project_variable_costs', 'incurred_date')) {
            DB::statement('ALTER TABLE project_variable_costs MODIFY incurred_date DATE NOT NULL');
        }

        Schema::table('project_variable_costs', function (Blueprint $table) {
            $table->index('incurred_date');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('project_variable_costs', function (Blueprint $table) {
            $table->dropIndex(['incurred_date']);
            $table->dropIndex(['category']);
            if (Schema::hasColumn('project_variable_costs', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('project_variable_costs', 'incurred_date')) {
                $table->dropColumn('incurred_date');
            }
        });
    }
};
