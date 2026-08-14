<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('returnable');
            $table->timestamp('removed_at')->nullable()->after('is_archived');
            $table->index(['is_archived', 'removed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['is_archived', 'removed_at']);
            $table->dropColumn(['is_archived', 'removed_at']);
        });
    }
};
