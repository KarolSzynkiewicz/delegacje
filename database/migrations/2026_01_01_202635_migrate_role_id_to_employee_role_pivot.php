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
        // Sprawdź czy tabela employees istnieje i ma kolumnę role_id
        if (!Schema::hasTable('employees') || !Schema::hasColumn('employees', 'role_id')) {
            return; // Tabela nie istnieje lub kolumna już nie istnieje, nic nie rób
        }
        
        // Sprawdź czy tabela employee_role istnieje
        if (!Schema::hasTable('employee_role')) {
            return; // Tabela pivot nie istnieje, nic nie rób
        }
        
        // Migrate existing role_id data to pivot table (tylko jeśli są dane do migracji)
        $employeesWithRole = DB::table('employees')->whereNotNull('role_id')->count();
        if ($employeesWithRole > 0) {
            $now = DB::getDriverName() === 'sqlite' ? "datetime('now')" : 'NOW()';
            try {
                DB::statement("
                    INSERT INTO employee_role (employee_id, role_id, created_at, updated_at)
                    SELECT id, role_id, {$now}, {$now}
                    FROM employees
                    WHERE role_id IS NOT NULL
                    AND NOT EXISTS (
                        SELECT 1 FROM employee_role er 
                        WHERE er.employee_id = employees.id AND er.role_id = employees.role_id
                    )
                ");
            } catch (\Exception $e) {
                // Może być problem z duplikatami - ignoruj
            }
        }

        // Remove role_id column from employees table
        Schema::table('employees', function (Blueprint $table) {
            // SQLite doesn't support dropping foreign keys
            if (DB::getDriverName() !== 'sqlite') {
                try {
                    $table->dropForeign(['role_id']);
                } catch (\Exception $e) {
                    // Foreign key może nie istnieć
                }
            }
            $table->dropColumn('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add role_id column back
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('phone')->constrained('roles')->onDelete('set null');
        });

        // Migrate data back from pivot (take first role if multiple)
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                UPDATE employees
                SET role_id = (
                    SELECT role_id 
                    FROM employee_role 
                    WHERE employee_role.employee_id = employees.id 
                    LIMIT 1
                )
            ");
        } else {
            DB::statement("
                UPDATE employees e
                INNER JOIN (
                    SELECT employee_id, MIN(role_id) as role_id
                    FROM employee_role
                    GROUP BY employee_id
                ) er ON e.id = er.employee_id
                SET e.role_id = er.role_id
            ");
        }
    }
};
