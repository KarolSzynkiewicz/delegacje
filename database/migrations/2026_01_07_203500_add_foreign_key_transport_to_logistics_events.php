<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('logistics_events') && Schema::hasTable('transports')) {
            Schema::table('logistics_events', function (Blueprint $table) {
                if (!Schema::hasColumn('logistics_events', 'transport_id')) {
                    $table->unsignedBigInteger('transport_id')->nullable()->after('vehicle_id');
                }
                // Add foreign key constraint if it doesn't exist
                $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('logistics_events');
                $hasForeignKey = false;
                foreach ($foreignKeys as $foreignKey) {
                    if (in_array('transport_id', $foreignKey->getLocalColumns())) {
                        $hasForeignKey = true;
                        break;
                    }
                }
                if (!$hasForeignKey) {
                    $table->foreign('transport_id')->references('id')->on('transports')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('logistics_events')) {
            Schema::table('logistics_events', function (Blueprint $table) {
                $table->dropForeign(['transport_id']);
            });
        }
    }
};
