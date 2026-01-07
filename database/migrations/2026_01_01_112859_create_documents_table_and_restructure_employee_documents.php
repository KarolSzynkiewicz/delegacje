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
        // 1. Sprawdź czy tabela documents już istnieje, jeśli nie - stwórz
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                // Sprawdź czy document_types istnieje przed utworzeniem klucza obcego
                if (Schema::hasTable('document_types')) {
                    $table->unsignedBigInteger('document_type_id')->nullable();
                } else {
                    $table->unsignedBigInteger('document_type_id')->nullable();
                }
                $table->text('notes')->nullable(); // ogólne notatki o dokumencie
                $table->timestamps();
            });
            
            // Dodaj klucz obcy osobno tylko jeśli document_types istnieje i klucz nie istnieje
            if (Schema::hasTable('document_types') && Schema::hasColumn('documents', 'document_type_id')) {
                if (DB::getDriverName() !== 'sqlite') {
                    // Sprawdź czy klucz obcy już istnieje
                    $fkExists = DB::selectOne("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'documents' 
                        AND COLUMN_NAME = 'document_type_id' 
                        AND REFERENCED_TABLE_NAME = 'document_types'
                    ");
                    
                    if (!$fkExists) {
                        // Dodaj klucz obcy tylko jeśli nie istnieje
                        try {
                            DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_document_type_id_foreign FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE RESTRICT");
                        } catch (\Exception $e) {
                            // Klucz obcy może już istnieć z inną nazwą - ignoruj
                        }
                    }
                }
            }
        }

        // 2. Przenieś dane z employee_documents do documents (tylko jeśli documents jest pusta)
        $documentsCount = DB::table('documents')->count();
        if ($documentsCount == 0) {
            DB::statement("
                INSERT INTO documents (document_type_id, notes, created_at, updated_at)
                SELECT DISTINCT document_type_id, notes, MIN(created_at), MIN(updated_at)
                FROM employee_documents
                GROUP BY document_type_id, notes
            ");
        }

        // 3. Sprawdź czy document_id już istnieje w employee_documents
        if (!Schema::hasColumn('employee_documents', 'document_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->foreignId('document_id')->nullable()->after('employee_id')->constrained('documents')->onDelete('cascade');
            });
        }

        // 4. Zaktualizuj document_id na podstawie document_type_id i notes (tylko dla NULL)
        if (DB::getDriverName() === 'sqlite') {
            // SQLite syntax
            DB::statement("
                UPDATE employee_documents
                SET document_id = (
                    SELECT d.id FROM documents d 
                    WHERE d.document_type_id = employee_documents.document_type_id 
                        AND (d.notes = employee_documents.notes OR (d.notes IS NULL AND employee_documents.notes IS NULL))
                    LIMIT 1
                )
                WHERE document_id IS NULL
            ");
        } else {
            // MySQL syntax
            DB::statement("
                UPDATE employee_documents ed
                INNER JOIN documents d ON ed.document_type_id = d.document_type_id 
                    AND (ed.notes = d.notes OR (ed.notes IS NULL AND d.notes IS NULL))
                SET ed.document_id = d.id
                WHERE ed.document_id IS NULL
            ");
        }

        // 5. Usuń document_type_id z employee_documents (jeśli istnieje)
        if (Schema::hasColumn('employee_documents', 'document_type_id')) {
            if (DB::getDriverName() !== 'sqlite') {
                // MySQL: Najpierw znajdź nazwę foreign key
                $fkName = DB::selectOne("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'employee_documents' 
                    AND COLUMN_NAME = 'document_type_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if ($fkName) {
                    DB::statement("ALTER TABLE employee_documents DROP FOREIGN KEY `{$fkName->CONSTRAINT_NAME}`");
                }
            }
            
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropColumn('document_type_id');
            });
        }

        // 6. Usuń notes z employee_documents (jeśli istnieje)
        if (Schema::hasColumn('employee_documents', 'notes')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Dodaj z powrotem document_type_id i notes do employee_documents
        if (!Schema::hasColumn('employee_documents', 'document_type_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->foreignId('document_type_id')->nullable()->after('employee_id')->constrained('document_types')->onDelete('restrict');
            });
        }
        if (!Schema::hasColumn('employee_documents', 'notes')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->text('notes')->nullable();
            });
        }

        // 2. Przywróć document_type_id i notes z documents
        if (DB::getDriverName() === 'sqlite') {
            // SQLite syntax
            DB::statement("
                UPDATE employee_documents
                SET document_type_id = (
                    SELECT d.document_type_id FROM documents d
                    WHERE d.id = employee_documents.document_id
                    LIMIT 1
                ),
                notes = (
                    SELECT d.notes FROM documents d
                    WHERE d.id = employee_documents.document_id
                    LIMIT 1
                )
                WHERE document_id IS NOT NULL
            ");
        } else {
            // MySQL syntax
            DB::statement("
                UPDATE employee_documents ed
                INNER JOIN documents d ON ed.document_id = d.id
                SET ed.document_type_id = d.document_type_id,
                    ed.notes = d.notes
            ");
        }

        // 3. Usuń document_id z employee_documents
        if (Schema::hasColumn('employee_documents', 'document_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                // SQLite doesn't support dropping foreign keys
                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['document_id']);
                }
                $table->dropColumn('document_id');
            });
        }

        // 4. Nie usuwamy tabeli documents (może być używana)
    }
};
