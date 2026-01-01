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
        // Migrate existing role_id data to pivot table
        DB::statement("
            INSERT INTO employee_role (employee_id, role_id, created_at, updated_at)
            SELECT id, role_id, NOW(), NOW()
            FROM employees
            WHERE role_id IS NOT NULL
        ");

        // Remove role_id column from employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
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
