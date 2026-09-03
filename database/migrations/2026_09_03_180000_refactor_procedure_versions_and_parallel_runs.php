<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('procedure_template_versions', 'version_number')) {
            Schema::table('procedure_template_versions', function (Blueprint $table) {
                $table->unsignedInteger('version_number')->default(1)->after('procedure_template_id');
            });
        }

        if (! Schema::hasColumn('procedure_runs', 'procedure_template_version_id')) {
            Schema::table('procedure_runs', function (Blueprint $table) {
                $table->foreignId('procedure_template_version_id')
                    ->nullable()
                    ->after('procedure_template_id')
                    ->constrained('procedure_template_versions')
                    ->restrictOnDelete();

                $table->json('active_node_ids')->nullable()->after('procedure_template_version_id');
            });
        }

        if (! Schema::hasColumn('procedure_run_steps', 'spawned_from_step_id')) {
            Schema::table('procedure_run_steps', function (Blueprint $table) {
                $table->foreignId('spawned_from_step_id')
                    ->nullable()
                    ->after('procedure_run_id')
                    ->constrained('procedure_run_steps')
                    ->nullOnDelete();
            });
        }

        $this->backfillVersionsAndRuns();

        if (! $this->indexExists('procedure_template_versions', 'ptv_template_version_unique')) {
            Schema::table('procedure_template_versions', function (Blueprint $table) {
                $table->unique(['procedure_template_id', 'version_number'], 'ptv_template_version_unique');
            });
        }

        if (Schema::hasColumn('procedure_runs', 'definition_snapshot')) {
            Schema::table('procedure_runs', function (Blueprint $table) {
                $table->dropColumn(['definition_snapshot', 'current_node_id']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('procedure_runs', 'definition_snapshot')) {
            Schema::table('procedure_runs', function (Blueprint $table) {
                $table->json('definition_snapshot')->nullable()->after('procedure_template_id');
                $table->string('current_node_id')->nullable()->after('subject_id');
            });

            $runs = DB::table('procedure_runs')->orderBy('id')->get();

            foreach ($runs as $run) {
                $version = DB::table('procedure_template_versions')
                    ->where('id', $run->procedure_template_version_id)
                    ->first();

                $active = json_decode($run->active_node_ids ?? '[]', true) ?: [];

                DB::table('procedure_runs')->where('id', $run->id)->update([
                    'definition_snapshot' => $version?->definition ?? json_encode(['nodes' => [], 'edges' => []]),
                    'current_node_id' => $active[0] ?? 'start-1',
                ]);
            }
        }

        if (Schema::hasColumn('procedure_runs', 'procedure_template_version_id')) {
            Schema::table('procedure_runs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('procedure_template_version_id');
                $table->dropColumn('active_node_ids');
            });
        }

        if (Schema::hasColumn('procedure_run_steps', 'spawned_from_step_id')) {
            Schema::table('procedure_run_steps', function (Blueprint $table) {
                $table->dropConstrainedForeignId('spawned_from_step_id');
            });
        }

        if ($this->indexExists('procedure_template_versions', 'ptv_template_version_unique')) {
            Schema::table('procedure_template_versions', function (Blueprint $table) {
                $table->dropUnique('ptv_template_version_unique');
            });
        }

        if (Schema::hasColumn('procedure_template_versions', 'version_number')) {
            Schema::table('procedure_template_versions', function (Blueprint $table) {
                $table->dropColumn('version_number');
            });
        }
    }

    private function backfillVersionsAndRuns(): void
    {
        DB::table('procedure_template_versions')->delete();

        $templates = DB::table('procedure_templates')->orderBy('id')->get();

        foreach ($templates as $template) {
            $definition = json_decode($template->definition, true) ?: ['nodes' => [], 'edges' => []];

            $versionId = DB::table('procedure_template_versions')->insertGetId([
                'procedure_template_id' => $template->id,
                'version_number' => 1,
                'definition' => json_encode($definition),
                'changed_by' => $template->created_by,
                'changed_at' => $template->updated_at ?? $template->created_at ?? now(),
            ]);

            $update = ['procedure_template_version_id' => $versionId];

            if (Schema::hasColumn('procedure_runs', 'current_node_id')) {
                DB::table('procedure_runs')
                    ->where('procedure_template_id', $template->id)
                    ->update(array_merge($update, [
                        'active_node_ids' => DB::raw(
                            "COALESCE(JSON_ARRAY(current_node_id), JSON_ARRAY('start-1'))"
                        ),
                    ]));
            } else {
                DB::table('procedure_runs')
                    ->where('procedure_template_id', $template->id)
                    ->update(array_merge($update, [
                        'active_node_ids' => json_encode(['start-1']),
                    ]));
            }
        }

        DB::table('procedure_runs')
            ->whereNull('procedure_template_version_id')
            ->update([
                'active_node_ids' => json_encode(['start-1']),
            ]);
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = DB::select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return ($result[0]->aggregate ?? 0) > 0;
    }
};
