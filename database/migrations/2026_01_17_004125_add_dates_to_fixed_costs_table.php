<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fixed_costs', function (Blueprint $table) {
            $table->date('cost_date')->after('currency');
            $table->date('start_date')->after('cost_date');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fixed_costs', function (Blueprint $table) {
            $table->dropColumn(['cost_date', 'start_date', 'end_date']);
        });
    }
};
