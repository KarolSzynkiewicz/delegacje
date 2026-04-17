<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wielosegmentowa trasa wyjazdu (transport publiczny: loty + transfery ziemne) — JSON; null = zapis legacy.
     */
    public function up(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            if (! Schema::hasColumn('logistics_events', 'route_segments')) {
                $table->json('route_segments')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            if (Schema::hasColumn('logistics_events', 'route_segments')) {
                $table->dropColumn('route_segments');
            }
        });
    }
};
