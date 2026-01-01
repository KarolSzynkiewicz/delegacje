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
        // SQLite doesn't support MODIFY COLUMN or ENUM
        // Skip this migration for SQLite - it's only needed for MySQL
        if (DB::getDriverName() === 'sqlite') {
            // Just update the data values for SQLite
            DB::statement("UPDATE employee_documents SET kind = 'okresowy' WHERE kind = 'terminowy'");
            DB::statement("UPDATE employee_documents SET kind = 'bezokresowy' WHERE kind = 'bezterminowy'");
            return;
        }
        
        // Zmień wartości enum z terminowy/bezterminowy na okresowy/bezokresowy
        DB::statement("ALTER TABLE employee_documents MODIFY COLUMN kind ENUM('okresowy', 'bezokresowy') DEFAULT 'okresowy'");
        
        // Zaktualizuj istniejące dane
        DB::statement("UPDATE employee_documents SET kind = 'okresowy' WHERE kind = 'terminowy'");
        DB::statement("UPDATE employee_documents SET kind = 'bezokresowy' WHERE kind = 'bezterminowy'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Zmień wartości enum z powrotem
        DB::statement("ALTER TABLE employee_documents MODIFY COLUMN kind ENUM('terminowy', 'bezterminowy') DEFAULT 'terminowy'");
        
        // Zaktualizuj istniejące dane
        DB::statement("UPDATE employee_documents SET kind = 'terminowy' WHERE kind = 'okresowy'");
        DB::statement("UPDATE employee_documents SET kind = 'bezterminowy' WHERE kind = 'bezokresowy'");
    }
};
