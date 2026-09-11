<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('forum_posts', 'cover_focal_x')) {
                $table->unsignedTinyInteger('cover_focal_x')->default(50)->after('image_path');
            }
            if (! Schema::hasColumn('forum_posts', 'cover_focal_y')) {
                $table->unsignedTinyInteger('cover_focal_y')->default(50)->after('cover_focal_x');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            if (Schema::hasColumn('forum_posts', 'cover_focal_x')) {
                $table->dropColumn('cover_focal_x');
            }
            if (Schema::hasColumn('forum_posts', 'cover_focal_y')) {
                $table->dropColumn('cover_focal_y');
            }
        });
    }
};
