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
        Schema::table('logistics_event_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('logistics_event_participants', 'restoration_payload')) {
                $table->json('restoration_payload')->nullable()->after('original_end_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_event_participants', function (Blueprint $table) {
            if (Schema::hasColumn('logistics_event_participants', 'restoration_payload')) {
                $table->dropColumn('restoration_payload');
            }
        });
    }
};
