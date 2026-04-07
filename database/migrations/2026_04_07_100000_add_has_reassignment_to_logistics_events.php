<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->boolean('has_reassignment')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropColumn('has_reassignment');
        });
    }
};
