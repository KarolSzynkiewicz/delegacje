<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sprint_definitions_of_done')) {
            return;
        }

        if (! Schema::hasColumn('sprint_dod_items', 'sprint_id')) {
            Schema::table('sprint_dod_items', function (Blueprint $table) {
                $table->foreignId('sprint_id')->nullable()->after('id')->constrained('sprints')->cascadeOnDelete();
            });
        }

        $groups = DB::table('sprint_definitions_of_done as dod')
            ->join('sprint_milestones as ms', 'ms.id', '=', 'dod.sprint_milestone_id')
            ->get([
                'dod.id',
                'dod.name',
                'ms.sprint_id',
            ]);

        foreach ($groups as $group) {
            $items = DB::table('sprint_dod_items')
                ->where('sprint_definition_of_done_id', $group->id)
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            if ($items->isEmpty()) {
                $position = (int) DB::table('sprint_dod_items')->where('sprint_id', $group->sprint_id)->max('position');
                DB::table('sprint_dod_items')->insert([
                    'sprint_id' => $group->sprint_id,
                    'name' => $group->name,
                    'completed_at' => null,
                    'position' => $position + 1,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'sprint_definition_of_done_id' => $group->id,
                ]);

                continue;
            }

            foreach ($items as $item) {
                DB::table('sprint_dod_items')->where('id', $item->id)->update([
                    'sprint_id' => $group->sprint_id,
                ]);
            }
        }

        Schema::table('sprint_dod_items', function (Blueprint $table) {
            $table->dropForeign(['sprint_definition_of_done_id']);
            $table->dropColumn('sprint_definition_of_done_id');
        });

        Schema::dropIfExists('sprint_definitions_of_done');
    }

    public function down(): void
    {
        // Nested DoD groups are not restored.
    }
};
