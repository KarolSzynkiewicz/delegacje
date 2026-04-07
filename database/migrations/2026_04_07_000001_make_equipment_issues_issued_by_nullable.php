<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_issues', function (Blueprint $table) {
            // Drop FK first (name is deterministic for Laravel foreignId: {table}_{column}_foreign)
            $table->dropForeign('equipment_issues_issued_by_foreign');
        });

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable()->change();
            $table->foreign('issued_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->dropForeign('equipment_issues_issued_by_foreign');
        });

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable(false)->change();
            $table->foreign('issued_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }
};
