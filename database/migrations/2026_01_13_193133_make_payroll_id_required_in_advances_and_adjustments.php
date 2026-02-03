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
        
        // Zmień payroll_id na required w adjustments - użyj bezpośredniego SQL
        try {
            // Pobierz nazwę foreign key z bazy danych
            $result = DB::selectOne("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'adjustments' 
                AND COLUMN_NAME = 'payroll_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ");
            
            if ($result && isset($result->CONSTRAINT_NAME)) {
                $fkName = $result->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE adjustments DROP FOREIGN KEY `{$fkName}`");
            }
        } catch (\Exception $e) {
            // Foreign key może nie istnieć - kontynuuj
        }
        
        // Zmień kolumnę na not null (bez foreign key)
        DB::statement('ALTER TABLE adjustments MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
        
        // Dodaj foreign key z powrotem z onDelete('cascade')
        DB::statement("ALTER TABLE adjustments ADD CONSTRAINT adjustments_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE");
        
        // Zmień payroll_id na required w advances - użyj bezpośredniego SQL
        try {
            // Pobierz nazwę foreign key z bazy danych
            $result = DB::selectOne("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'advances' 
                AND COLUMN_NAME = 'payroll_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ");
            
            if ($result && isset($result->CONSTRAINT_NAME)) {
                $fkName = $result->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE advances DROP FOREIGN KEY `{$fkName}`");
            }
        } catch (\Exception $e) {
            // Foreign key może nie istnieć - kontynuuj
        }
        
        // Zmień kolumnę na not null (bez foreign key)
        DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
        
        // Dodaj foreign key z powrotem z onDelete('cascade')
        DB::statement("ALTER TABLE advances ADD CONSTRAINT advances_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE");
        
        // Włącz sprawdzanie foreign key constraints
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        
        // adjustments
        DB::statement("ALTER TABLE adjustments DROP FOREIGN KEY adjustments_payroll_id_foreign");
        DB::statement('ALTER TABLE adjustments MODIFY payroll_id BIGINT UNSIGNED NULL');
        DB::statement("ALTER TABLE adjustments ADD CONSTRAINT adjustments_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE SET NULL");
        
        // advances
        DB::statement("ALTER TABLE advances DROP FOREIGN KEY advances_payroll_id_foreign");
        DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NULL');
        DB::statement("ALTER TABLE advances ADD CONSTRAINT advances_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE SET NULL");
        
        Schema::enableForeignKeyConstraints();
    }
};
