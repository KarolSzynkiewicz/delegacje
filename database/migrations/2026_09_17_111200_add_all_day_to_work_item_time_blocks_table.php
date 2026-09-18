<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_item_time_blocks', function (Blueprint $table) {
            $table->boolean('all_day')->default(false)->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_item_time_blocks', function (Blueprint $table) {
            $table->dropColumn('all_day');
        });
    }
};
