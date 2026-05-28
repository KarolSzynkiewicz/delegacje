<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accommodation_leases', function (Blueprint $table) {
            if (!Schema::hasColumn('accommodation_leases', 'monthly_rent')) {
                $table->decimal('monthly_rent', 10, 2)->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('accommodation_leases', 'currency')) {
                $table->string('currency', 3)->nullable()->after('monthly_rent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_leases', function (Blueprint $table) {
            if (Schema::hasColumn('accommodation_leases', 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn('accommodation_leases', 'monthly_rent')) {
                $table->dropColumn('monthly_rent');
            }
        });
    }
};
