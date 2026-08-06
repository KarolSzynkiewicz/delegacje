<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_grid_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 100);
            $table->string('status', 50)->default('');
            $table->string('flag', 50)->default('');
            $table->boolean('mine')->default(false);
            $table->boolean('former_employee')->default(false);
            $table->string('recruiter', 50)->default('');
            $table->string('referral_source', 80)->default('');
            $table->string('rejection_filter', 80)->default('');
            $table->string('search', 255)->default('');
            $table->string('sort_field', 50)->default('created_at');
            $table->string('sort_direction', 4)->default('desc');
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_grid_views');
    }
};
