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
        Schema::table('transport_costs', function (Blueprint $table) {
            // Dodaj brakujące kolumny jeśli nie istnieją
            if (!Schema::hasColumn('transport_costs', 'logistics_event_id')) {
                $table->foreignId('logistics_event_id')->nullable()->after('id')->constrained('logistics_events')->onDelete('set null');
            }
            if (!Schema::hasColumn('transport_costs', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('logistics_event_id')->constrained('vehicles')->onDelete('set null');
            }
            if (!Schema::hasColumn('transport_costs', 'transport_id')) {
                $table->foreignId('transport_id')->nullable()->after('vehicle_id')->constrained('transports')->onDelete('set null');
            }
            if (!Schema::hasColumn('transport_costs', 'cost_type')) {
                $table->string('cost_type')->after('transport_id');
            }
            if (!Schema::hasColumn('transport_costs', 'amount')) {
                $table->decimal('amount', 10, 2)->after('cost_type');
            }
            if (!Schema::hasColumn('transport_costs', 'currency')) {
                $table->string('currency')->default('PLN')->after('amount');
            }
            if (!Schema::hasColumn('transport_costs', 'cost_date')) {
                $table->date('cost_date')->after('currency');
            }
            if (!Schema::hasColumn('transport_costs', 'description')) {
                $table->text('description')->nullable()->after('cost_date');
            }
            if (!Schema::hasColumn('transport_costs', 'notes')) {
                $table->text('notes')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('transport_costs', 'created_by')) {
                $table->foreignId('created_by')->after('notes')->constrained('users')->onDelete('restrict');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transport_costs', function (Blueprint $table) {
            // Usuń kolumny
            if (Schema::hasColumn('transport_costs', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('transport_costs', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('transport_costs', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('transport_costs', 'cost_date')) {
                $table->dropColumn('cost_date');
            }
            if (Schema::hasColumn('transport_costs', 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn('transport_costs', 'amount')) {
                $table->dropColumn('amount');
            }
            if (Schema::hasColumn('transport_costs', 'cost_type')) {
                $table->dropColumn('cost_type');
            }
            if (Schema::hasColumn('transport_costs', 'transport_id')) {
                $table->dropForeign(['transport_id']);
                $table->dropColumn('transport_id');
            }
            if (Schema::hasColumn('transport_costs', 'vehicle_id')) {
                $table->dropForeign(['vehicle_id']);
                $table->dropColumn('vehicle_id');
            }
            if (Schema::hasColumn('transport_costs', 'logistics_event_id')) {
                $table->dropForeign(['logistics_event_id']);
                $table->dropColumn('logistics_event_id');
            }
        });
    }
};
