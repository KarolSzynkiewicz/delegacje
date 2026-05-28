<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_cost_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_cost_templates', 'category')) {
                $table->string('category', 64)->nullable()->after('currency');
                $table->index('category');
            }
        });

        Schema::table('fixed_cost_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_cost_entries', 'category')) {
                $table->string('category', 64)->nullable()->after('currency');
                $table->index('category');
            }
        });

        // Backfill kategorii w entries z powiązanego templatu (jeśli istnieje)
        DB::statement('
            UPDATE fixed_cost_entries e
            INNER JOIN fixed_cost_templates t ON t.id = e.template_id
            SET e.category = t.category
            WHERE e.category IS NULL AND t.category IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('fixed_cost_entries', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_cost_entries', 'category')) {
                $table->dropIndex(['category']);
                $table->dropColumn('category');
            }
        });

        Schema::table('fixed_cost_templates', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_cost_templates', 'category')) {
                $table->dropIndex(['category']);
                $table->dropColumn('category');
            }
        });
    }
};
