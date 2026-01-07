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
        if (Schema::hasTable('project_demands')) {
            // Sprawdź czy tabela ma starą strukturę (start_date, end_date, required_workers_count)
            if (Schema::hasColumn('project_demands', 'start_date') && !Schema::hasColumn('project_demands', 'role_id')) {
                // Dodaj nowe kolumny
                Schema::table('project_demands', function (Blueprint $table) {
                    $table->foreignId('role_id')->nullable()->after('project_id')->constrained()->onDelete('cascade');
                    $table->integer('required_count')->default(1)->after('role_id');
                    $table->date('date_from')->nullable()->after('required_count');
                    $table->date('date_to')->nullable()->after('date_from');
                });

                // Skopiuj dane ze starych kolumn do nowych
                DB::table('project_demands')->update([
                    'date_from' => DB::raw('start_date'),
                    'date_to' => DB::raw('end_date'),
                    'required_count' => DB::raw('required_workers_count'),
                ]);

                // Usuń stare kolumny
                Schema::table('project_demands', function (Blueprint $table) {
                    $table->dropColumn(['start_date', 'end_date', 'required_workers_count']);
                });

                // Ustaw role_id jako NOT NULL (ale tylko jeśli są dane)
                $hasData = DB::table('project_demands')->whereNotNull('role_id')->exists();
                if ($hasData) {
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->foreignId('role_id')->nullable(false)->change();
                        $table->date('date_from')->nullable(false)->change();
                    });
                }
            } else {
                // Tabela już ma nową strukturę, tylko upewnij się że ma wszystkie kolumny
                if (!Schema::hasColumn('project_demands', 'role_id')) {
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->foreignId('role_id')->nullable()->after('project_id')->constrained()->onDelete('cascade');
                    });
                }
                if (!Schema::hasColumn('project_demands', 'required_count')) {
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->integer('required_count')->default(1)->after('role_id');
                    });
                }
                if (!Schema::hasColumn('project_demands', 'date_from')) {
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->date('date_from')->nullable()->after('required_count');
                    });
                }
                if (!Schema::hasColumn('project_demands', 'date_to')) {
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->date('date_to')->nullable()->after('date_from');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nie cofamy tej migracji - to jest naprawa struktury
    }
};
