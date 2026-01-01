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
        // 1. Dodaj kolumnę type do employee_documents
        if (!Schema::hasColumn('employee_documents', 'type')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->string('type')->nullable()->after('employee_id');
            });
        }

        // 2. Jeśli istnieje document_id, przenieś dane z documents
        if (Schema::hasColumn('employee_documents', 'document_id')) {
            // Pobierz typ dokumentu z documents->documentType
            if (DB::getDriverName() === 'sqlite') {
                // SQLite syntax
                DB::statement("
                    UPDATE employee_documents
                    SET type = (
                        SELECT dt.name FROM documents d
                        INNER JOIN document_types dt ON d.document_type_id = dt.id
                        WHERE d.id = employee_documents.document_id
                        LIMIT 1
                    )
                    WHERE type IS NULL
                ");
            } else {
                // MySQL syntax
                DB::statement("
                    UPDATE employee_documents ed
                    INNER JOIN documents d ON ed.document_id = d.id
                    INNER JOIN document_types dt ON d.document_type_id = dt.id
                    SET ed.type = dt.name
                    WHERE ed.type IS NULL
                ");
            }
        }

        // 3. Dodaj notes jeśli nie ma
        if (!Schema::hasColumn('employee_documents', 'notes')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('kind');
            });
        }

        // 4. Jeśli istnieje document_id, przenieś notes z documents
        if (Schema::hasColumn('employee_documents', 'document_id') && Schema::hasColumn('documents', 'notes')) {
            if (DB::getDriverName() === 'sqlite') {
                // SQLite syntax
                DB::statement("
                    UPDATE employee_documents
                    SET notes = (
                        SELECT d.notes FROM documents d
                        WHERE d.id = employee_documents.document_id
                        LIMIT 1
                    )
                    WHERE notes IS NULL AND document_id IS NOT NULL
                ");
            } else {
                // MySQL syntax
                DB::statement("
                    UPDATE employee_documents ed
                    INNER JOIN documents d ON ed.document_id = d.id
                    SET ed.notes = d.notes
                    WHERE ed.notes IS NULL AND d.notes IS NOT NULL
                ");
            }
        }

        // 5. Usuń document_id z employee_documents
        if (Schema::hasColumn('employee_documents', 'document_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropForeign(['document_id']);
                $table->dropColumn('document_id');
            });
        }

        // 6. Ustaw type jako required (nie nullable)
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->string('type')->nullable(false)->change();
        });

        // 7. Usuń tabele documents i document_types
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nie będziemy cofać - to jest uproszczenie
    }
};
