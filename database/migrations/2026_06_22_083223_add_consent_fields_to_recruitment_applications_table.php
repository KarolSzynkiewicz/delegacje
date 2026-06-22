<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->boolean('consent_rodo')->default(false)->after('photo_path');
            $table->boolean('consent_recruitment_processing')->default(false)->after('consent_rodo');
            $table->boolean('consent_marketing')->default(false)->after('consent_recruitment_processing');
            $table->timestamp('consent_given_at')->nullable()->after('consent_marketing');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn([
                'consent_rodo',
                'consent_recruitment_processing',
                'consent_marketing',
                'consent_given_at',
            ]);
        });
    }
};
