<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->dropColumn('expected_return_date');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->date('expected_return_date')->nullable()->after('issue_date');
        });
    }
};
