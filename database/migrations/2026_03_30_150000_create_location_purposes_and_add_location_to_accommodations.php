<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_purposes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 32);
            $table->timestamps();

            $table->unique(['location_id', 'purpose']);
        });

        if (!Schema::hasColumn('accommodations', 'location_id')) {
            Schema::table('accommodations', function (Blueprint $table) {
                $table->foreignId('location_id')->nullable()->after('id')->constrained('locations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('accommodations', 'location_id')) {
            Schema::table('accommodations', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        Schema::dropIfExists('location_purposes');
    }
};
