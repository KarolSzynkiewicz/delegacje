<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Wyłącz sprawdzanie foreign key constraints podczas migracji
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Najpierw usuń wszystkie advances i adjustments bez payroll_id (jeśli są)
        DB::table('adjustments')->whereNull('payroll_id')->delete();
        DB::table('advances')->whereNull('payroll_id')->delete();
        
        // Zmień payroll_id na required w adjustments - użyj bezpośredniego SQL
        // Pobierz wszystkie foreign key constraints TYLKO dla payroll_id
        $fkConstraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'adjustments' 
            AND COLUMN_NAME = 'payroll_id' 
            AND REFERENCED_TABLE_NAME = 'payrolls'
        ");
        
        foreach ($fkConstraints as $fk) {
            try {
                DB::statement("ALTER TABLE adjustments DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Ignoruj błędy - foreign key może już nie istnieć
            }
            }
            
        // Zmień kolumnę na not null (bez foreign key)
            DB::statement('ALTER TABLE adjustments MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
            
        // Dodaj foreign key z powrotem z onDelete('cascade')
        DB::statement("ALTER TABLE adjustments ADD CONSTRAINT adjustments_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE");
        
        // Zmień payroll_id na required w advances - użyj bezpośredniego SQL
        // Pobierz wszystkie foreign key constraints TYLKO dla payroll_id (referencja do payrolls)
        $fkConstraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'advances' 
            AND COLUMN_NAME = 'payroll_id' 
            AND REFERENCED_TABLE_NAME = 'payrolls'
            AND REFERENCED_COLUMN_NAME = 'id'
        ");
        
        foreach ($fkConstraints as $fk) {
            $fkName = $fk->CONSTRAINT_NAME;
            // Upewnij się, że to nie jest employee_id foreign key
            if (strpos($fkName, 'employee') === false && strpos($fkName, 'payroll') !== false) {
                try {
                    DB::statement("ALTER TABLE advances DROP FOREIGN KEY `{$fkName}`");
            } catch (\Exception $e) {
                    // Ignoruj błędy - foreign key może już nie istnieć
                }
            }
            }
            
        // Zmień kolumnę na not null (bez foreign key)
            DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
            
        // Dodaj foreign key z powrotem z onDelete('cascade')
        DB::statement("ALTER TABLE advances ADD CONSTRAINT advances_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE");
        
        // Włącz sprawdzanie foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // adjustments
        try {
            DB::statement("ALTER TABLE adjustments DROP FOREIGN KEY adjustments_payroll_id_foreign");
        } catch (\Exception $e) {
            // Ignoruj jeśli nie istnieje
        }
            DB::statement('ALTER TABLE adjustments MODIFY payroll_id BIGINT UNSIGNED NULL');
        DB::statement("ALTER TABLE adjustments ADD CONSTRAINT adjustments_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE SET NULL");
        
        // advances
        try {
            DB::statement("ALTER TABLE advances DROP FOREIGN KEY advances_payroll_id_foreign");
        } catch (\Exception $e) {
            // Ignoruj jeśli nie istnieje
        }
            DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NULL');
        DB::statement("ALTER TABLE advances ADD CONSTRAINT advances_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE SET NULL");
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
