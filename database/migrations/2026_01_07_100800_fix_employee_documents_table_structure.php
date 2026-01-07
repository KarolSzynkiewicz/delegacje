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
        if (Schema::hasTable('employee_documents')) {
            // Sprawdź czy tabela ma starą strukturę (document_type_id zamiast document_id)
            if (Schema::hasColumn('employee_documents', 'document_type_id') && !Schema::hasColumn('employee_documents', 'document_id')) {
                // Dodaj kolumnę document_id
                Schema::table('employee_documents', function (Blueprint $table) {
                    $table->foreignId('document_id')->nullable()->after('employee_id')->constrained('documents')->onDelete('restrict');
                });

                // Przenieś dane z document_type_id do document_id (jeśli istnieje tabela document_types)
                if (Schema::hasTable('document_types')) {
                    if (DB::getDriverName() === 'sqlite') {
                        DB::statement("
                            UPDATE employee_documents
                            SET document_id = (
                                SELECT d.id 
                                FROM documents d
                                INNER JOIN document_types dt ON d.document_type_id = dt.id
                                WHERE dt.id = employee_documents.document_type_id
                                LIMIT 1
                            )
                            WHERE document_id IS NULL
                        ");
                    } else {
                        DB::statement("
                            UPDATE employee_documents ed
                            INNER JOIN document_types dt ON ed.document_type_id = dt.id
                            INNER JOIN documents d ON d.document_type_id = dt.id
                            SET ed.document_id = d.id
                            WHERE ed.document_id IS NULL
                        ");
                    }
                }

                // Usuń starą kolumnę document_type_id
                if (Schema::hasColumn('employee_documents', 'document_type_id')) {
                    Schema::table('employee_documents', function (Blueprint $table) {
                        // SQLite doesn't support dropping foreign keys
                        if (DB::getDriverName() !== 'sqlite') {
                            // Sprawdź czy foreign key istnieje przed usunięciem
                            $foreignKeys = DB::select("
                                SELECT CONSTRAINT_NAME 
                                FROM information_schema.KEY_COLUMN_USAGE 
                                WHERE TABLE_SCHEMA = DATABASE() 
                                AND TABLE_NAME = 'employee_documents' 
                                AND COLUMN_NAME = 'document_type_id' 
                                AND REFERENCED_TABLE_NAME IS NOT NULL
                            ");
                            
                            if (!empty($foreignKeys)) {
                                foreach ($foreignKeys as $fk) {
                                    try {
                                        $table->dropForeign([$fk->CONSTRAINT_NAME]);
                                    } catch (\Exception $e) {
                                        // Spróbuj standardowej nazwy
                                        try {
                                            $table->dropForeign(['employee_documents_document_type_id_foreign']);
                                        } catch (\Exception $e2) {
                                            // Ignoruj jeśli nie istnieje
                                        }
                                    }
                                }
                            }
                        }
                        $table->dropColumn('document_type_id');
                    });
                }
            }
            
            // Upewnij się że kolumna document_id istnieje i jest NOT NULL (jeśli są dane)
            if (Schema::hasColumn('employee_documents', 'document_id')) {
                $hasNullDocuments = DB::table('employee_documents')->whereNull('document_id')->exists();
                if (!$hasNullDocuments) {
                    try {
                        Schema::table('employee_documents', function (Blueprint $table) {
                            $table->foreignId('document_id')->nullable(false)->change();
                        });
                    } catch (\Exception $e) {
                        // Może być problem z change(), pomijamy
                    }
                }
            }
            
            // Dodaj kolumnę file_path jeśli nie istnieje
            if (!Schema::hasColumn('employee_documents', 'file_path')) {
                Schema::table('employee_documents', function (Blueprint $table) {
                    $table->string('file_path')->nullable()->after('notes');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nie cofamy tej migracji - to jest naprawa struktury
    }
};
