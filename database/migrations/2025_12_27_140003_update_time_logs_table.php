<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
                try {
                    $table->dropForeign(['delegation_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, try alternative name
                    try {
                        DB::statement('ALTER TABLE time_logs DROP FOREIGN KEY time_logs_delegation_id_foreign');
                    } catch (\Exception $e2) {
                        // Ignore if doesn't exist
                    }
                }
                $table->dropColumn('delegation_id');
            }

            // Add new foreign key to project_assignments if column doesn't exist
            if (!Schema::hasColumn('time_logs', 'project_assignment_id')) {
                $table->foreignId('project_assignment_id')
                    ->after('id')
                    ->constrained()
                    ->onDelete('cascade');
            } else {
                // Column exists but might not have foreign key - add it
                if (DB::getDriverName() !== 'sqlite') {
                    try {
                        $table->foreign('project_assignment_id')
                            ->references('id')
                            ->on('project_assignments')
                            ->onDelete('cascade');
                    } catch (\Exception $e) {
                        // Foreign key might already exist
                    }
                }
            }
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
