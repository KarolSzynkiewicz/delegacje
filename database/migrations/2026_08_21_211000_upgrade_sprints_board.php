<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->dropColumn('definition_of_ready');
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->unsignedInteger('sprint_position')->nullable()->after('sprint_id');
        });

        Schema::create('sprint_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->date('due_date');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sprint_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_milestones');

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn('sprint_position');
        });

        Schema::table('sprints', function (Blueprint $table) {
            $table->text('definition_of_ready')->nullable()->after('goal');
        });
    }
};
