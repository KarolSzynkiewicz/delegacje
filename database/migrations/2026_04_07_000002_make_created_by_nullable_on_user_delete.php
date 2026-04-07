<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // logistics_events.created_by -> nullOnDelete
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropForeign('logistics_events_created_by_foreign');
        });
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // transport_costs.created_by -> nullOnDelete
        Schema::table('transport_costs', function (Blueprint $table) {
            $table->dropForeign('transport_costs_created_by_foreign');
        });
        Schema::table('transport_costs', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropForeign('logistics_events_created_by_foreign');
        });
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('transport_costs', function (Blueprint $table) {
            $table->dropForeign('transport_costs_created_by_foreign');
        });
        Schema::table('transport_costs', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
