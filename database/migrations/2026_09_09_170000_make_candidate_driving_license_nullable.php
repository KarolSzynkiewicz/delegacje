<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->boolean('has_driving_license_b')->nullable()->default(null)->change();
        });

        // Previous false meant "checkbox off" — unknown, not an explicit "nie ma".
        DB::table('recruitment_candidates')
            ->where('has_driving_license_b', false)
            ->update(['has_driving_license_b' => null]);
    }

    public function down(): void
    {
        DB::table('recruitment_candidates')
            ->whereNull('has_driving_license_b')
            ->update(['has_driving_license_b' => false]);

        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->boolean('has_driving_license_b')->nullable(false)->default(false)->change();
        });
    }
};
