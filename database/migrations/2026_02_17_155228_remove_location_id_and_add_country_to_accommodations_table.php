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
        Schema::table('accommodations', function (Blueprint $table) {
            // Remove location_id foreign key and column
            if (Schema::hasColumn('accommodations', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
            
            // Add country column
            if (!Schema::hasColumn('accommodations', 'country')) {
                $table->string('country', 2)->nullable()->after('postal_code')->comment('ISO 3166-1 alpha-2 country code');
                $table->index('country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            // Remove country column
            if (Schema::hasColumn('accommodations', 'country')) {
                $table->dropIndex(['country']);
                $table->dropColumn('country');
            }
            
            // Restore location_id
            if (!Schema::hasColumn('accommodations', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('postal_code')->constrained('locations')->onDelete('set null');
            }
        });
    }
};
