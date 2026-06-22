<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_applications', function (Blueprint $table) {
            $table->id();

            // Dane podstawowe kandydata (odpowiadają polom Employee)
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();

            // Stanowisko, o które kandydat się ubiega (tekst wolny)
            $table->string('desired_role')->nullable();

            // List motywacyjny / wiadomość od kandydata
            $table->text('cover_letter')->nullable();

            // Zdjęcie kandydata (ścieżka do pliku)
            $table->string('photo_path')->nullable();

            // Status rekrutacji
            $table->enum('status', ['pending', 'reviewing', 'accepted', 'rejected', 'converted'])
                ->default('pending')
                ->index();

            // Notatki wewnętrzne (widoczne tylko dla administratorów)
            $table->text('admin_notes')->nullable();

            // Powiązanie z pracownikiem po konwersji
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_applications');
    }
};
