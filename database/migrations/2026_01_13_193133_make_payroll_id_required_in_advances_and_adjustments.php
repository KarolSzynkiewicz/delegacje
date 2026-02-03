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
            // Usuń foreign key constraint na payroll_id (nie dotykamy employee_id ani indeksów)
            try {
                $table->dropForeign(['adjustments_payroll_id_foreign']);
            } catch (\Exception $e) {
                try {
                    $table->dropForeign(['payroll_id']);
                } catch (\Exception $e2) {
                    // Foreign key może nie istnieć lub mieć inną nazwę - sprawdź wszystkie możliwe
                    try {
                        DB::statement("ALTER TABLE adjustments DROP FOREIGN KEY IF EXISTS adjustments_payroll_id_foreign");
                    } catch (\Exception $e3) {
                        // Ignoruj jeśli nie istnieje
                    }
                }
            }
        });
        
        // Zmień kolumnę na not null (bez foreign key)
        DB::statement('ALTER TABLE adjustments MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
        
        // Dodaj foreign key z powrotem z onDelete('cascade')
        Schema::table('adjustments', function (Blueprint $table) {
            // Dodaj foreign key na payroll_id (employee_id i indeksy już istnieją)
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
        });
        
        // Zmień payroll_id na required w advances
        Schema::table('advances', function (Blueprint $table) {
            // Usuń foreign key constraint na payroll_id (nie dotykamy employee_id ani indeksów)
            try {
                $table->dropForeign(['advances_payroll_id_foreign']);
            } catch (\Exception $e) {
                try {
                    $table->dropForeign(['payroll_id']);
                } catch (\Exception $e2) {
                    // Foreign key może nie istnieć lub mieć inną nazwę - sprawdź wszystkie możliwe
                    try {
                        DB::statement("ALTER TABLE advances DROP FOREIGN KEY IF EXISTS advances_payroll_id_foreign");
                    } catch (\Exception $e3) {
                        // Ignoruj jeśli nie istnieje
                    }
                }
            }
        });
        
        // Zmień kolumnę na not null (bez foreign key)
        DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
        
        // Dodaj foreign key z powrotem z onDelete('cascade')
        Schema::table('advances', function (Blueprint $table) {
            // Dodaj foreign key na payroll_id (employee_id i indeksy już istnieją)
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
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
