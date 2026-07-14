<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_grid_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 100);
            $table->json('visible_columns');
            $table->json('column_widths')->nullable();
            $table->string('group_by', 50)->default('');
            $table->string('sort_field', 50)->default('created_at');
            $table->string('sort_direction', 4)->default('desc');
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_grid_views');
    }
};
