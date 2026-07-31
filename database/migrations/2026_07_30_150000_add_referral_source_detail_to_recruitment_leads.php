<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->string('referral_source_detail')->nullable()->after('referral_source');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->dropColumn('referral_source_detail');
        });
    }
};
