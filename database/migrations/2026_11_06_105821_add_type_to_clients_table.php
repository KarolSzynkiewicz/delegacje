<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clients') || Schema::hasColumn('clients', 'type')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->string('type', 32)->default('client')->after('name');
            $table->index('type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('clients') || ! Schema::hasColumn('clients', 'type')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
