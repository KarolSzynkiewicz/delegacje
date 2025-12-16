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
        // Add foreign key to projects table
        Schema::table("projects", function (Blueprint $table) {
            $table->foreign("location_id")
                  ->references("id")
                  ->on("locations")
                  ->onDelete("cascade");
        });

        // Add foreign keys to delegations table
        Schema::table("delegations", function (Blueprint $table) {
            $table->foreign("employee_id")
                  ->references("id")
                  ->on("users") // Assuming "users" is the table for employees
                  ->onDelete("cascade");

            $table->foreign("project_id")
                  ->references("id")
                  ->on("projects")
                  ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("projects", function (Blueprint $table) {
            $table->dropForeign(["location_id"]);
        });

        Schema::table("delegations", function (Blueprint $table) {
            $table->dropForeign(["employee_id"]);
            $table->dropForeign(["project_id"]);
        });
    }
};
