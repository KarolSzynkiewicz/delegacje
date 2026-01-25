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
        Schema::table('transport_costs', function (Blueprint $table) {
            if (Schema::hasColumn('transport_costs', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
            if (!Schema::hasColumn('transport_costs', 'file_path')) {
                $table->string('file_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transport_costs', function (Blueprint $table) {
            if (Schema::hasColumn('transport_costs', 'file_path')) {
                $table->dropColumn('file_path');
            }
            if (!Schema::hasColumn('transport_costs', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->after('description');
            }
        });
    }
};
