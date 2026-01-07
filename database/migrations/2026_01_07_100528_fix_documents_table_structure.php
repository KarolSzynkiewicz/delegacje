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
        // Sprawdź czy tabela documents istnieje i ma starą strukturę (document_type_id)
        if (Schema::hasTable('documents')) {
            // Sprawdź czy ma kolumnę document_type_id (stara struktura)
            if (Schema::hasColumn('documents', 'document_type_id')) {
                // Przekształć tabelę na nową strukturę z kolumną name
                
                // 1. Dodaj kolumny name i description jeśli nie istnieją
                if (!Schema::hasColumn('documents', 'name')) {
                    Schema::table('documents', function (Blueprint $table) {
                        $table->string('name')->nullable()->after('id');
                    });
                }
                
                if (!Schema::hasColumn('documents', 'description')) {
                    Schema::table('documents', function (Blueprint $table) {
                        $table->text('description')->nullable()->after('name');
                    });
                }
                
                // 2. Jeśli istnieje tabela document_types, przenieś nazwy do kolumny name
                if (Schema::hasTable('document_types')) {
                    if (DB::getDriverName() === 'sqlite') {
                        DB::statement("
                            UPDATE documents
                            SET name = (
                                SELECT dt.name 
                                FROM document_types dt 
                                WHERE dt.id = documents.document_type_id
                                LIMIT 1
                            )
                            WHERE name IS NULL
                        ");
                    } else {
                        DB::statement("
                            UPDATE documents d
                            INNER JOIN document_types dt ON d.document_type_id = dt.id
                            SET d.name = dt.name
                            WHERE d.name IS NULL
                        ");
                    }
                }
                
                // 3. Usuń duplikaty - zostaw tylko jeden dokument dla każdej nazwy
                if (DB::getDriverName() === 'sqlite') {
                    // SQLite nie obsługuje DELETE z JOIN, więc użyjemy podzapytania
                    DB::statement("
                        DELETE FROM documents
                        WHERE id NOT IN (
                            SELECT MIN(id)
                            FROM documents
                            WHERE name IS NOT NULL
                            GROUP BY name
                        )
                    ");
                } else {
                    DB::statement("
                        DELETE d1 FROM documents d1
                        INNER JOIN documents d2
                        WHERE d1.id > d2.id AND d1.name = d2.name AND d1.name IS NOT NULL
                    ");
                }
                
                // 4. Ustaw name jako NOT NULL i unique
                // Najpierw usuń NULL values jeśli są
                DB::table('documents')->whereNull('name')->delete();
                
                // Sprawdź czy unique constraint już istnieje
                $hasUnique = false;
                try {
                    if (DB::getDriverName() === 'sqlite') {
                        $indexes = DB::select("PRAGMA index_list(documents)");
                        foreach ($indexes as $index) {
                            $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                            foreach ($indexInfo as $info) {
                                if ($info->name === 'name' && $index->unique == 1) {
                                    $hasUnique = true;
                                    break 2;
                                }
                            }
                        }
                    } else {
                        $indexes = DB::select("SHOW INDEXES FROM documents WHERE Column_name = 'name' AND Non_unique = 0");
                        $hasUnique = !empty($indexes);
                    }
                } catch (\Exception $e) {
                    // Jeśli nie można sprawdzić, założymy że nie ma
                    $hasUnique = false;
                }
                
                Schema::table('documents', function (Blueprint $table) use ($hasUnique) {
                    if (!$hasUnique && DB::getDriverName() !== 'sqlite') {
                        // Dla MySQL dodaj unique constraint przed zmianą
                        $table->unique('name');
                    }
                });
                
                // Zmień na NOT NULL
                Schema::table('documents', function (Blueprint $table) {
                    $table->string('name')->nullable(false)->change();
                });
                
                // Dla SQLite dodaj unique constraint osobno jeśli nie istnieje
                if (!$hasUnique && DB::getDriverName() === 'sqlite') {
                    try {
                        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS documents_name_unique ON documents(name)');
                    } catch (\Exception $e) {
                        // Index może już istnieć
                    }
                }
                
                // 5. Dodaj kolumnę is_periodic jeśli nie istnieje
                if (!Schema::hasColumn('documents', 'is_periodic')) {
                    Schema::table('documents', function (Blueprint $table) {
                        $table->boolean('is_periodic')->default(true)->after('description');
                    });
                }
                
                // 6. Usuń starą kolumnę document_type_id jeśli istnieje
                if (Schema::hasColumn('documents', 'document_type_id')) {
                    try {
                        Schema::table('documents', function (Blueprint $table) {
                            // SQLite doesn't support dropping foreign keys
                            if (DB::getDriverName() !== 'sqlite') {
                                try {
                                    $table->dropForeign(['document_type_id']);
                                } catch (\Exception $e) {
                                    // Foreign key może nie istnieć
                                }
                            }
                            $table->dropColumn('document_type_id');
                        });
                    } catch (\Exception $e) {
                        // Może być problem z usunięciem kolumny, spróbujmy bezpośrednio SQL
                        if (DB::getDriverName() === 'sqlite') {
                            // SQLite wymaga rekreacji tabeli
                            // Pomijamy to na razie - kolumna nie przeszkadza
                        }
                    }
                }
                
                // 7. Usuń kolumnę notes jeśli istnieje (nie jest używana w nowym modelu)
                if (Schema::hasColumn('documents', 'notes')) {
                    Schema::table('documents', function (Blueprint $table) {
                        $table->dropColumn('notes');
                    });
                }
            } else {
                // Tabela już ma nową strukturę, tylko upewnij się że ma wszystkie kolumny
                if (!Schema::hasColumn('documents', 'is_periodic')) {
                    Schema::table('documents', function (Blueprint $table) {
                        $table->boolean('is_periodic')->default(true)->after('description');
                    });
                }
            }
        } else {
            // Tabela nie istnieje - stwórz ją z nową strukturą
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_periodic')->default(true);
                $table->timestamps();
            });
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
