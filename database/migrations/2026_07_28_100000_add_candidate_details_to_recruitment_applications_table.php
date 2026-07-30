<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('phone');
            $table->boolean('has_driving_license_b')->default(false)->after('city');
            $table->boolean('speaks_english')->default(false)->after('has_driving_license_b');
            $table->boolean('speaks_french')->default(false)->after('speaks_english');
            $table->boolean('speaks_german')->default(false)->after('speaks_french');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'has_driving_license_b',
                'speaks_english',
                'speaks_french',
                'speaks_german',
            ]);
        });
    }
};
