<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::statement('ALTER TABLE advances DROP FOREIGN KEY advances_payroll_id_foreign');
        } catch (\Exception $e) {
            // ignore if FK name differs
        }

        DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE advances ADD CONSTRAINT advances_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE SET NULL');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::statement('ALTER TABLE advances DROP FOREIGN KEY advances_payroll_id_foreign');
        } catch (\Exception $e) {
            // ignore
        }

        DB::statement('ALTER TABLE advances MODIFY payroll_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE advances ADD CONSTRAINT advances_payroll_id_foreign FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
