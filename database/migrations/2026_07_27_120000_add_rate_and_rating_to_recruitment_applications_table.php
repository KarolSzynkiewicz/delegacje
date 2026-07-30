<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->decimal('expected_rate_eur', 6, 2)->nullable()->after('referral_source');
            $table->string('rating', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn(['expected_rate_eur', 'rating']);
        });
    }
};
