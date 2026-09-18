<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_item_time_blocks', function (Blueprint $table) {
            $table->dropForeign(['work_item_id']);
        });

        Schema::table('work_item_time_blocks', function (Blueprint $table) {
            $table->unsignedBigInteger('work_item_id')->nullable()->change();
            $table->string('kind', 16)->default('item')->after('work_item_id');
            $table->string('title')->nullable()->after('kind');
            $table->foreign('work_item_id')->references('id')->on('work_items')->cascadeOnDelete();
            $table->index(['user_id', 'kind', 'starts_at']);
        });

        Schema::create('work_item_time_block_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_time_block_id')->constrained('work_item_time_blocks')->cascadeOnDelete();
            $table->foreignId('work_item_id')->constrained('work_items')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['work_item_time_block_id', 'work_item_id'], 'time_block_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_time_block_items');

        Schema::table('work_item_time_blocks', function (Blueprint $table) {
            $table->dropForeign(['work_item_id']);
            $table->dropIndex(['user_id', 'kind', 'starts_at']);
            $table->dropColumn(['kind', 'title']);
        });

        Schema::table('work_item_time_blocks', function (Blueprint $table) {
            $table->unsignedBigInteger('work_item_id')->nullable(false)->change();
            $table->foreign('work_item_id')->references('id')->on('work_items')->cascadeOnDelete();
        });
    }
};
