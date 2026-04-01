<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adjustments', function (Blueprint $table) {
            $table->foreignId('logistics_event_id')
                ->nullable()
                ->after('payroll_id')
                ->constrained('logistics_events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('adjustments', function (Blueprint $table) {
            $table->dropForeign(['logistics_event_id']);
            $table->dropColumn('logistics_event_id');
        });
    }
};
