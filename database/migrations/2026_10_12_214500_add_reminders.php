<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_item_time_blocks', function (Blueprint $table) {
            $table->boolean('remind')->default(true)->after('all_day');
            $table->timestamp('reminder_sent_for')->nullable()->after('remind');
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->boolean('remind')->default(true)->after('ends_at');
            $table->timestamp('reminder_sent_for')->nullable()->after('remind');
        });

        Schema::table('work_items', function (Blueprint $table) {
            $table->date('deadline_reminded_on')->nullable()->after('due_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->time('deadline_reminder_time')->default('09:00:00');
        });
    }

    public function down(): void
    {
        Schema::table('work_item_time_blocks', function (Blueprint $table) {
            $table->dropColumn(['remind', 'reminder_sent_for']);
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn(['remind', 'reminder_sent_for']);
        });

        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn('deadline_reminded_on');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deadline_reminder_time');
        });
    }
};
