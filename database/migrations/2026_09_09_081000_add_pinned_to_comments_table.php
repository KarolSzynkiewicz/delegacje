<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->boolean('pinned')->default(false)->after('body');
            $table->index(['commentable_type', 'commentable_id', 'pinned']);
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['commentable_type', 'commentable_id', 'pinned']);
            $table->dropColumn('pinned');
        });
    }
};
