<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_grid_views', function (Blueprint $table) {
            $table->json('advanced_filters')->nullable()->after('rejection_filter');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_grid_views', function (Blueprint $table) {
            $table->dropColumn('advanced_filters');
        });
    }
};
