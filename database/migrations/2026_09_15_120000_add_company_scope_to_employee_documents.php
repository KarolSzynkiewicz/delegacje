<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('is_company_scoped')
                ->default(false)
                ->after('is_required');
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('document_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('is_company_scoped');
        });
    }
};
