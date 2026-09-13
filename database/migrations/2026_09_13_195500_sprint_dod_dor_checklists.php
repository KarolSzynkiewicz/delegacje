<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprint_readiness_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sprint_id', 'position']);
        });

        Schema::create('sprint_dod_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sprint_id', 'position']);
        });

        $this->migrateLegacyDefinitionOfDone();

        Schema::table('sprints', function (Blueprint $table) {
            $table->dropColumn('definition_of_done');
        });
    }

    public function down(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->text('definition_of_done')->nullable()->after('goal');
        });

        Schema::dropIfExists('sprint_dod_items');
        Schema::dropIfExists('sprint_readiness_items');
    }

    private function migrateLegacyDefinitionOfDone(): void
    {
        $sprints = DB::table('sprints')
            ->whereNotNull('definition_of_done')
            ->where('definition_of_done', '!=', '')
            ->get(['id', 'definition_of_done']);

        foreach ($sprints as $sprint) {
            $lines = $this->lines((string) $sprint->definition_of_done);
            foreach ($lines as $index => $line) {
                DB::table('sprint_dod_items')->insert([
                    'sprint_id' => $sprint->id,
                    'name' => $line,
                    'completed_at' => null,
                    'position' => $index,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function lines(string $text): array
    {
        $parts = preg_split('/\r\n|\r|\n/', $text) ?: [];

        return collect($parts)
            ->map(function (string $line) {
                $trimmed = trim($line);
                $trimmed = preg_replace('/^[-*•]\s+/u', '', $trimmed) ?? $trimmed;

                return trim($trimmed);
            })
            ->filter()
            ->values()
            ->all();
    }
};
