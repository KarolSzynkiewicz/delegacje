<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            // Usuń foreign key constraint
            $table->dropForeign(['project_id']);
            // Zmień kolumnę na nullable
            $table->foreignId('project_id')->nullable()->change();
            // Dodaj z powrotem foreign key constraint (nullable)
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            // Usuń foreign key constraint
            $table->dropForeign(['project_id']);
            // Zmień kolumnę na NOT NULL (tylko jeśli nie ma NULL wartości)
            $table->foreignId('project_id')->nullable(false)->change();
            // Dodaj z powrotem foreign key constraint
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }
};
