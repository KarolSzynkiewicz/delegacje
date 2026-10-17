<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_list_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_global')->default(false);
            $table->string('name');
            $table->string('slug', 100);
            $table->json('visible_columns');
            $table->string('sort_field', 50)->default('last_name');
            $table->string('sort_direction', 4)->default('asc');
            $table->json('filters');
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_list_views');
    }
};
