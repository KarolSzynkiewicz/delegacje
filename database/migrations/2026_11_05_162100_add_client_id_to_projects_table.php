<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects') || Schema::hasColumn('projects', 'client_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('location_id')
                ->constrained('clients')
                ->nullOnDelete();
        });

        if (! Schema::hasTable('clients') || ! Schema::hasColumn('projects', 'client_name')) {
            return;
        }

        $names = DB::table('projects')
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->orderBy('client_name')
            ->pluck('client_name');

        foreach ($names as $name) {
            $trimmed = trim((string) $name);
            if ($trimmed === '') {
                continue;
            }

            $existingId = DB::table('clients')->where('name', $trimmed)->value('id');
            if (! $existingId) {
                $existingId = DB::table('clients')->insertGetId([
                    'name' => $trimmed,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('projects')
                ->where('client_name', $name)
                ->whereNull('client_id')
                ->update(['client_id' => $existingId]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'client_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
