<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('time_logs') || Schema::hasColumn('time_logs', 'client_hours')) {
            return;
        }

        Schema::table('time_logs', function (Blueprint $table) {
            // Godziny wg klienta — osobna warstwa od hours_worked (wewnętrzne).
            // null = jeszcze nie skopiowano / brak opinii klienta.
            $table->decimal('client_hours', 5, 2)->nullable()->after('hours_worked');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('time_logs') || ! Schema::hasColumn('time_logs', 'client_hours')) {
            return;
        }

        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropColumn('client_hours');
        });
    }
};
