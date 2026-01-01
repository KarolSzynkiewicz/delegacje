<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Dodaj kolumnę document_id do employee_documents
        if (!Schema::hasColumn('employee_documents', 'document_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->foreignId('document_id')->nullable()->after('employee_id')->constrained('documents')->onDelete('restrict');
            });
        }

        // 2. Przenieś dane z type (string) do document_id
        // Najpierw stwórz rekordy w documents na podstawie istniejących wartości type
        if (Schema::hasColumn('employee_documents', 'type')) {
            $now = DB::getDriverName() === 'sqlite' ? "datetime('now')" : 'NOW()';
            
            // Wstaw unikalne typy dokumentów do tabeli documents
            DB::statement("
                INSERT INTO documents (name, created_at, updated_at)
                SELECT DISTINCT type, {$now}, {$now}
                FROM employee_documents
                WHERE type IS NOT NULL AND type != ''
                AND NOT EXISTS (
                    SELECT 1 FROM documents WHERE documents.name = employee_documents.type
                )
            ");

            // Zaktualizuj document_id na podstawie type
            if (DB::getDriverName() === 'sqlite') {
                DB::statement("
                    UPDATE employee_documents
                    SET document_id = (
                        SELECT id FROM documents WHERE documents.name = employee_documents.type
                        LIMIT 1
                    )
                    WHERE type IS NOT NULL AND type != ''
                ");
            } else {
                DB::statement("
                    UPDATE employee_documents ed
                    INNER JOIN documents d ON ed.type = d.name
                    SET ed.document_id = d.id
                    WHERE ed.type IS NOT NULL AND ed.type != ''
                ");
            }
        }

        // 3. Ustaw document_id jako required (nie nullable)
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreignId('document_id')->nullable(false)->change();
        });

        // 4. Usuń kolumnę type
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
        // Dodaj z powrotem kolumnę type
        if (!Schema::hasColumn('employee_documents', 'type')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->string('type')->after('employee_id');
            });
        }

        // Przywróć dane z document_id do type
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                UPDATE employee_documents
                SET type = (
                    SELECT name FROM documents WHERE documents.id = employee_documents.document_id
                    LIMIT 1
                )
                WHERE document_id IS NOT NULL
            ");
        } else {
            DB::statement("
                UPDATE employee_documents ed
                INNER JOIN documents d ON ed.document_id = d.id
                SET ed.type = d.name
            ");
        }

        // Usuń document_id
        if (Schema::hasColumn('employee_documents', 'document_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropForeign(['document_id']);
                $table->dropColumn('document_id');
            });
        }
    }
};
