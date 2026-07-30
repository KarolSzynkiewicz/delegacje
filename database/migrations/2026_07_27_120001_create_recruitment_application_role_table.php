<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_application_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recruitment_application_id', 'role_id'], 'recruitment_application_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_application_role');
    }
};
