<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_stock_movements', function (Blueprint $table) {
            $table->string('consumed_for_type')->nullable();
            $table->unsignedBigInteger('consumed_for_id')->nullable();
            $table->index(['consumed_for_type', 'consumed_for_id'], 'eq_stock_mov_consumed_for_index');
        });

        DB::table('equipment_stock_movements')
            ->whereNotNull('employee_id')
            ->whereNull('consumed_for_id')
            ->update([
                'consumed_for_type' => 'employee',
                'consumed_for_id' => DB::raw('employee_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('equipment_stock_movements', function (Blueprint $table) {
            $table->dropIndex('eq_stock_mov_consumed_for_index');
            $table->dropColumn(['consumed_for_type', 'consumed_for_id']);
        });
    }
};
