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
        // Sprawdź czy kolumna role_id istnieje w tabeli employees
        if (Schema::hasColumn('employees', 'role_id')) {
            Schema::table('employees', function (Blueprint $table) {
                // SQLite doesn't support dropping foreign keys
                if (DB::getDriverName() !== 'sqlite') {
                    // Sprawdź czy foreign key istnieje przed usunięciem
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'employees' 
                        AND COLUMN_NAME = 'role_id' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    
                    if (!empty($foreignKeys)) {
                        foreach ($foreignKeys as $fk) {
                            try {
                                $table->dropForeign([$fk->CONSTRAINT_NAME]);
                            } catch (\Exception $e) {
                                // Spróbuj standardowej nazwy
                                try {
                                    $table->dropForeign(['employees_role_id_foreign']);
                                } catch (\Exception $e2) {
                                    // Ignoruj jeśli nie istnieje
                                }
                            }
                        }
                    }
                }
                $table->dropColumn('role_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dodaj kolumnę z powrotem jeśli potrzebne
        if (!Schema::hasColumn('employees', 'role_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->after('phone')->constrained('roles')->onDelete('set null');
            });
        }
    }
};
