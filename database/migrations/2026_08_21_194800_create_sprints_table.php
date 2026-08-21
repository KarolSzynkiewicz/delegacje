<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('goal')->nullable();
            $table->text('definition_of_ready')->nullable();
            $table->text('definition_of_done')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreignId('sprint_id')->nullable()->after('project_id')
                ->constrained('sprints')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropForeign(['sprint_id']);
            $table->dropColumn('sprint_id');
        });

        Schema::dropIfExists('sprints');
    }
};
