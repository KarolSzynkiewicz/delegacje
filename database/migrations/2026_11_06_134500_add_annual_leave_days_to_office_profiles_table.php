<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('office_profiles') || Schema::hasColumn('office_profiles', 'annual_leave_days')) {
            return;
        }

        Schema::table('office_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('annual_leave_days')
                ->default(26)
                ->after('fte');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('office_profiles') || ! Schema::hasColumn('office_profiles', 'annual_leave_days')) {
            return;
        }

        Schema::table('office_profiles', function (Blueprint $table) {
            $table->dropColumn('annual_leave_days');
        });
    }
};
