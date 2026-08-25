<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->unique();
            // Szyfrowane przez cast 'encrypted' — w bazie leży ciphertext, nie klucz.
            $table->text('api_key');
            $table->string('model', 100)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_credentials');
    }
};
