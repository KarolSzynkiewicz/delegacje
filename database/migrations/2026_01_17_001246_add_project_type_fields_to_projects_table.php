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
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('type', ['hourly', 'contract'])->default('contract')->after('status');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('type');
            $table->decimal('contract_amount', 12, 2)->nullable()->after('hourly_rate');
            $table->string('currency', 3)->default('PLN')->after('contract_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['type', 'hourly_rate', 'contract_amount', 'currency']);
        });
    }
};
