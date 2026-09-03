<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_runs', function (Blueprint $table) {
            $table->json('join_tokens')->nullable()->after('active_node_ids');
        });
    }

    public function down(): void
    {
        Schema::table('procedure_runs', function (Blueprint $table) {
            $table->dropColumn('join_tokens');
        });
    }
};
