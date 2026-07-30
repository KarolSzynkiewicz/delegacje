<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_contact_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('outcome', 30);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['recruitment_application_id', 'created_at'], 'recruitment_contact_attempts_app_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_contact_attempts');
    }
};
