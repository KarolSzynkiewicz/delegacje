<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('time_logs') || Schema::hasColumn('time_logs', 'settled_hours')) {
            return;
        }

        Schema::table('time_logs', function (Blueprint $table) {
            // Konsensus po wyjaśnieniu rozbieżności nasze ↔ klient.
            $table->decimal('settled_hours', 5, 2)->nullable()->after('client_hours');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('time_logs') || ! Schema::hasColumn('time_logs', 'settled_hours')) {
            return;
        }

        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropColumn('settled_hours');
        });
    }
};
