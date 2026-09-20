<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('end_date');
            $table->timestamp('parked_at')->nullable()->after('closed_at');
            $table->index('closed_at');
            $table->index('parked_at');
        });
    }

    public function down(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->dropIndex(['closed_at']);
            $table->dropIndex(['parked_at']);
            $table->dropColumn(['closed_at', 'parked_at']);
        });
    }
};
