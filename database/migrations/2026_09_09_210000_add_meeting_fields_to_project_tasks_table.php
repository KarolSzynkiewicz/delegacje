<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('project_tasks', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('project_tasks', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (! Schema::hasColumn('project_tasks', 'participant_ids')) {
                $table->json('participant_ids')->nullable()->after('ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['starts_at', 'ends_at', 'participant_ids'],
                fn (string $column) => Schema::hasColumn('project_tasks', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
