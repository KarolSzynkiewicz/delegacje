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
            $table->string('email')->nullable()->change();
        });

        DB::table('recruitment_candidates')->where('email', '')->update(['email' => null]);
    }

    public function down(): void
    {
        DB::table('recruitment_candidates')->whereNull('email')->update(['email' => '']);

        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
