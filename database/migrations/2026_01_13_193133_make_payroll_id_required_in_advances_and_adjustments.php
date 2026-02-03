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
        // Wyłącz sprawdzanie foreign key constraints podczas migracji
        Schema::disableForeignKeyConstraints();
        
        // Najpierw usuń wszystkie advances i adjustments bez payroll_id (jeśli są)
        DB::table('adjustments')->whereNull('payroll_id')->delete();
        DB::table('advances')->whereNull('payroll_id')->delete();
        
        // Zmień payroll_id na required w adjustments
        Schema::table('adjustments', function (Blueprint $table) {
            // Usuń foreign key constraint PRZED usunięciem indeksu
            // Sprawdź różne możliwe nazwy foreign key
            $foreignKeys = ['adjustments_payroll_id_foreign', 'adjustments_payroll_id_foreign', 'payroll_id'];
            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign([$fk]);
                    break; // Jeśli udało się usunąć, przerwij
                } catch (\Exception $e) {
                    // Spróbuj następną nazwę
                    continue;
                }
            }
            
            // Usuń indeksy TYLKO jeśli nie są używane przez foreign key
            // Indeks employee_id_date może być używany przez foreign key employee_id
            // Nie usuwamy go, jeśli powoduje błąd
            try {
                $table->dropIndex(['employee_id', 'date']);
            } catch (\Exception $e) {
                // Indeks może być używany przez foreign key - pomiń
            }
        });
        
        // Zmień kolumnę na not null (bez foreign key)
        DB::statement('ALTER TABLE adjustments MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
        
        // Dodaj foreign key z powrotem z onDelete('cascade')
        Schema::table('adjustments', function (Blueprint $table) {
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->index(['employee_id', 'date']);
        });
        
        // Zmień payroll_id na required w advances
        Schema::table('advances', function (Blueprint $table) {
            // Usuń foreign key constraint PRZED usunięciem indeksu
            $foreignKeys = ['advances_payroll_id_foreign', 'advances_payroll_id_foreign', 'payroll_id'];
            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign([$fk]);
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Usuń indeksy TYLKO jeśli nie są używane przez foreign key
            try {
                $table->dropIndex(['employee_id', 'date']);
            } catch (\Exception $e) {
                // Indeks może być używany przez foreign key - pomiń
            }
        });
        
        // Zmień kolumnę na not null (bez foreign key)
        DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
        
        // Dodaj foreign key z powrotem z onDelete('cascade')
        Schema::table('advances', function (Blueprint $table) {
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->index(['employee_id', 'date']);
        });
        
        // Włącz sprawdzanie foreign key constraints
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adjustments', function (Blueprint $table) {
            $table->dropForeign(['payroll_id']);
            DB::statement('ALTER TABLE adjustments MODIFY payroll_id BIGINT UNSIGNED NULL');
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('set null');
            $table->index(['employee_id', 'date']);
        });
        
        Schema::table('advances', function (Blueprint $table) {
            $table->dropForeign(['payroll_id']);
            DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NULL');
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('set null');
            $table->index(['employee_id', 'date']);
        });
    }
};
