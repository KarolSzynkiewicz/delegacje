<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions') || Schema::hasColumn('push_subscriptions', 'user_agent')) {
            return;
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->text('user_agent')->nullable()->after('content_encoding');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('push_subscriptions', 'user_agent')) {
            return;
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};
