<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->index(['pinned', 'created_at']);
        });

        Schema::create('forum_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('forum_post_tag', function (Blueprint $table) {
            $table->foreignId('forum_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('forum_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['forum_post_id', 'forum_tag_id']);
        });

        Schema::create('forum_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['forum_post_id', 'user_id']);
        });

        Schema::create('forum_post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->unique(['forum_post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_post_views');
        Schema::dropIfExists('forum_post_likes');
        Schema::dropIfExists('forum_post_tag');
        Schema::dropIfExists('forum_tags');
        Schema::dropIfExists('forum_posts');
    }
};
