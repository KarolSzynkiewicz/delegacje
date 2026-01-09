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
        Schema::table('projects', function (Blueprint $table) {
            // Indeksy dla kolumn używanych w WHERE
            $table->index('status', 'projects_status_index');
            $table->index('location_id', 'projects_location_id_index');
            
            // Indeksy dla kolumn używanych w ORDER BY
            $table->index('name', 'projects_name_index');
            
            // Indeks dla kolumny używanej w LIKE search
            // Full-text index dla lepszej wydajności LIKE queries
            $table->index('client_name', 'projects_client_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_status_index');
            $table->dropIndex('projects_location_id_index');
            $table->dropIndex('projects_name_index');
            $table->dropIndex('projects_client_name_index');
        });
    }
};
