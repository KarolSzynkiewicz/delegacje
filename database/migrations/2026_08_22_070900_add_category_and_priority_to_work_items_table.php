<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->string('category')->nullable()->after('title');
            $table->unsignedTinyInteger('priority')->nullable()->after('category');
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropIndex(['category', 'status']);
            $table->dropColumn(['category', 'priority']);
        });
    }
};
