<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->boolean('sets_outside_base')->nullable()->after('has_reassignment');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropColumn('sets_outside_base');
        });
    }
};
