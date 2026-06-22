<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->string('referral_source', 50)->nullable()->after('desired_role');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn('referral_source');
        });
    }
};
