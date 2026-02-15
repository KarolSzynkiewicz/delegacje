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
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('outside_base')->default(false)->after('notes');
            $table->foreignId('last_departure_id')->nullable()->after('outside_base')->constrained('logistics_events')->nullOnDelete();
            
            $table->index('outside_base'); // dla szybkiego filtrowania
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['outside_base']);
            $table->dropForeign(['last_departure_id']);
            $table->dropColumn(['outside_base', 'last_departure_id']);
        });
    }
};
