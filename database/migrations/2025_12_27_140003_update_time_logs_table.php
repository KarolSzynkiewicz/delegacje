<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') === 'sqlite') {
            Schema::dropIfExists('time_logs');
            Schema::create('time_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_assignment_id')->constrained()->onDelete('cascade');
                $table->dateTime('start_time');
                $table->dateTime('end_time')->nullable();
                $table->decimal('hours_worked', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
            return;
        }

        Schema::table('time_logs', function (Blueprint $table) {
            // Drop old foreign key and column if exists
            if (Schema::hasColumn('time_logs', 'delegation_id')) {
                $table->dropForeign(['delegation_id']);
                $table->dropColumn('delegation_id');
            }

            // Add new foreign key to project_assignments
            $table->foreignId('project_assignment_id')
                ->after('id')
                ->constrained()
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            // Drop new foreign key
            $table->dropForeign(['project_assignment_id']);
            $table->dropColumn('project_assignment_id');

            // Restore old foreign key
            $table->foreignId('delegation_id')
                ->after('id')
                ->constrained()
                ->onDelete('cascade');
        });
    }
};
