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
            // Usuń stare indeksy jeśli istnieją
            try {
                $table->dropIndex(['employee_id', 'date']);
            } catch (\Exception $e) {
                // Indeks może nie istnieć
            }
            
            // Usuń foreign key constraint - sprawdź czy istnieje
            try {
                $table->dropForeign(['adjustments_payroll_id_foreign']);
            } catch (\Exception $e) {
                // Może mieć inną nazwę, spróbuj alternatywną
                try {
                    $table->dropForeign(['payroll_id']);
                } catch (\Exception $e2) {
                    // Foreign key może nie istnieć
                }
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
            // Usuń stare indeksy jeśli istnieją
            try {
                $table->dropIndex(['employee_id', 'date']);
            } catch (\Exception $e) {
                // Indeks może nie istnieć
            }
            
            // Usuń foreign key constraint - sprawdź czy istnieje
            try {
                $table->dropForeign(['advances_payroll_id_foreign']);
            } catch (\Exception $e) {
                // Może mieć inną nazwę, spróbuj alternatywną
                try {
                    $table->dropForeign(['payroll_id']);
                } catch (\Exception $e2) {
                    // Foreign key może nie istnieć
                }
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
