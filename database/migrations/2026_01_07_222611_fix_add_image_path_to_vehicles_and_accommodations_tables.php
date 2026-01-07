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
        // Add image_path to vehicles table if it doesn't exist
        if (Schema::hasTable('vehicles') && !Schema::hasColumn('vehicles', 'image_path')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('notes');
            });
        }

        // Add image_path to accommodations table if it doesn't exist
        if (Schema::hasTable('accommodations') && !Schema::hasColumn('accommodations', 'image_path')) {
            Schema::table('accommodations', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'image_path')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }

        if (Schema::hasTable('accommodations') && Schema::hasColumn('accommodations', 'image_path')) {
            Schema::table('accommodations', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }
};
