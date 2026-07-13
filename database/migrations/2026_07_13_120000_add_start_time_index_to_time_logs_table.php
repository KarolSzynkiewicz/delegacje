<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dashboard rentowności (i inne raporty) filtrują time_logs po zakresie dat
     * (`whereBetween('start_time', ...)`) bardzo często — bez indeksu na tej kolumnie
     * baza robi full table scan przy każdym takim zapytaniu, co przy większej liczbie
     * wpisów jest głównym wąskim gardłem wydajności tych widoków.
     */
    public function up(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            if (! $this->indexExists('time_logs', 'time_logs_start_time_index')) {
                $table->index('start_time');
            }
            if (! $this->indexExists('time_logs', 'time_logs_project_assignment_id_start_time_index')) {
                $table->index(['project_assignment_id', 'start_time']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            if ($this->indexExists('time_logs', 'time_logs_start_time_index')) {
                $table->dropIndex('time_logs_start_time_index');
            }
            if ($this->indexExists('time_logs', 'time_logs_project_assignment_id_start_time_index')) {
                $table->dropIndex('time_logs_project_assignment_id_start_time_index');
            }
        });
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getSchemaBuilder();

        if (! $schemaManager->hasTable($table)) {
            return false;
        }

        $indexes = $connection->select(
            $connection->getDriverName() === 'sqlite'
                ? "PRAGMA index_list({$table})"
                : "SHOW INDEX FROM {$table} WHERE Key_name = ?",
            $connection->getDriverName() === 'sqlite' ? [] : [$indexName]
        );

        if ($connection->getDriverName() === 'sqlite') {
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        return count($indexes) > 0;
    }
};
