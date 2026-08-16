<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_stock_movements', function (Blueprint $table) {
            $table->foreignId('related_warehouse_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('warehouses')
                ->nullOnDelete();
            $table->foreignId('logistics_event_id')
                ->nullable()
                ->after('batch_id')
                ->constrained('logistics_events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logistics_event_id');
            $table->dropConstrainedForeignId('related_warehouse_id');
        });
    }
};
