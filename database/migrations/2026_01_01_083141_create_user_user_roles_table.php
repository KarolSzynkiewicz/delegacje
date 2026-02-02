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
        // This table is replaced by Spatie's model_has_roles
        // Only create if it doesn't exist and Spatie table doesn't exist
        if (!Schema::hasTable('user_user_roles') && !Schema::hasTable('model_has_roles')) {
            // Check if user_roles table exists (created by Spatie Permission)
            $userRolesExists = Schema::hasTable('user_roles');
            
            Schema::create('user_user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                // Use unsignedBigInteger to avoid foreign key constraint error
                // if user_roles table doesn't exist yet (will be created by Spatie migration later)
                $table->unsignedBigInteger('user_role_id');
                $table->timestamps();
                $table->unique(['user_id', 'user_role_id']);
            });
            
            // Add foreign key constraint only if user_roles table exists
            if ($userRolesExists) {
                Schema::table('user_user_roles', function (Blueprint $table) {
                    $table->foreign('user_role_id')
                        ->references('id')
                        ->on('user_roles')
                        ->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_user_roles');
    }
};
