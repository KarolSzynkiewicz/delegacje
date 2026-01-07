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
        // Najpierw dodaj kolumnę document_type_id do documents
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('employee_id')->constrained('document_types')->onDelete('restrict');
        });

        // Przenieś dane z type (string) do document_type_id
        // Najpierw musimy stworzyć typy dokumentów na podstawie istniejących wartości
        $now = DB::getDriverName() === 'sqlite' ? "datetime('now')" : 'NOW()';
        DB::statement("
            INSERT INTO document_types (name, created_at, updated_at)
            SELECT DISTINCT type, {$now}, {$now}
            FROM documents
            WHERE type IS NOT NULL AND type != ''
        ");

        // Zaktualizuj document_type_id na podstawie type
        if (DB::getDriverName() === 'sqlite') {
            // SQLite syntax
            DB::statement("
                UPDATE documents
                SET document_type_id = (
                    SELECT id FROM document_types WHERE document_types.name = documents.type
                )
                WHERE type IS NOT NULL AND type != ''
            ");
        } else {
            // MySQL syntax
            DB::statement("
                UPDATE documents d
                INNER JOIN document_types dt ON d.type = dt.name
                SET d.document_type_id = dt.id
            ");
        }

        // Usuń kolumnę type
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Zmień nazwę tabeli z documents na employee_documents
        Schema::rename('documents', 'employee_documents');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Zmień nazwę tabeli z powrotem
        Schema::rename('employee_documents', 'documents');

        // Dodaj kolumnę type z powrotem
        Schema::table('documents', function (Blueprint $table) {
            $table->string('type')->after('employee_id');
        });

        // Przywróć dane z document_type_id do type
        DB::statement("
            UPDATE documents d
            INNER JOIN document_types dt ON d.document_type_id = dt.id
            SET d.type = dt.name
        ");

        // Usuń kolumnę document_type_id
        Schema::table('documents', function (Blueprint $table) {
            // SQLite doesn't support dropping foreign keys
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['document_type_id']);
            }
            $table->dropColumn('document_type_id');
        });
    }
};
