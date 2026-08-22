<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zastępują dawny pojedynczy przełącznik "moje zadania" (my_tasks_only)
     * dropdownem "przypisany do" (assigned_filter: '' | 'me' | user id) oraz
     * dawny "hide_callbacks" (nigdy nie zapisywany per widok) checkboxami
     * "typ pracy" (type_filter: lista wartości WorkItemType).
     */
    public function up(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->string('assigned_filter', 30)->default('')->after('my_tasks_only');
            $table->json('type_filter')->nullable()->after('assigned_filter');
        });
    }

    public function down(): void
    {
        Schema::table('task_grid_views', function (Blueprint $table) {
            $table->dropColumn(['assigned_filter', 'type_filter']);
        });
    }
};
