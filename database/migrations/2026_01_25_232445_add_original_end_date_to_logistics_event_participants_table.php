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
            $table->date('original_end_date')->nullable()->after('assignment_id')
                ->comment('Oryginalna data końca przypisania przed skróceniem przez zjazd. Używane do przywracania przypisań przy edycji zjazdu.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_event_participants', function (Blueprint $table) {
            $table->dropColumn('original_end_date');
        });
    }
};
