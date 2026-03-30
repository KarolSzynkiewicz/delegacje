<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('accommodations', 'type')) {
            Schema::table('accommodations', function (Blueprint $table) {
                $table->string('type', 32)->default('wynajmowany')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('accommodations', 'type')) {
            Schema::table('accommodations', function (Blueprint $table) {
                $table->string('type', 32)->default('własny')->change();
            });
        }
    }
};
