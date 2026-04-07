<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Ensure a technical "System" user exists
        $systemUserId = DB::table('users')->where('email', 'system@local')->value('id');
        if (! $systemUserId) {
            $systemUserId = DB::table('users')->insertGetId([
                'name' => 'System',
                'email' => 'system@local',
                'password' => bcrypt(str()->random(32)),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2) Backfill NULLs introduced by previous FK relaxations
        DB::table('equipment_issues')->whereNull('issued_by')->update(['issued_by' => $systemUserId]);
        DB::table('logistics_events')->whereNull('created_by')->update(['created_by' => $systemUserId]);
        DB::table('transport_costs')->whereNull('created_by')->update(['created_by' => $systemUserId]);

        // 3) Restore constraints to RESTRICT and NOT NULL
        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->dropForeign('equipment_issues_issued_by_foreign');
        });
        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable(false)->change();
            $table->foreign('issued_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropForeign('logistics_events_created_by_foreign');
        });
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('transport_costs', function (Blueprint $table) {
            $table->dropForeign('transport_costs_created_by_foreign');
        });
        Schema::table('transport_costs', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Reverse only constraints. Keep the system user and backfilled values.
        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->dropForeign('equipment_issues_issued_by_foreign');
        });
        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable()->change();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropForeign('logistics_events_created_by_foreign');
        });
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('transport_costs', function (Blueprint $table) {
            $table->dropForeign('transport_costs_created_by_foreign');
        });
        Schema::table('transport_costs', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
