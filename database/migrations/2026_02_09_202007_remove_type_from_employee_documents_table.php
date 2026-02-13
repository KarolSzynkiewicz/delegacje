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
        // Sprawdź czy kolumna istnieje przed usunięciem
        if (Schema::hasColumn('employee_documents', 'type')) {
        Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropColumn('type');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->string('type')->nullable()->after('document_id');
        });
    }
};
