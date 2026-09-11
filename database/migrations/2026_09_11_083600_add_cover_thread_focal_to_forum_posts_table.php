<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('forum_posts', 'cover_thread_x')) {
                $table->unsignedTinyInteger('cover_thread_x')->default(50)->after('cover_focal_y');
            }
            if (! Schema::hasColumn('forum_posts', 'cover_thread_y')) {
                $table->unsignedTinyInteger('cover_thread_y')->default(50)->after('cover_thread_x');
            }
        });

        if (Schema::hasColumn('forum_posts', 'cover_thread_x') && Schema::hasColumn('forum_posts', 'cover_focal_x')) {
            DB::table('forum_posts')->update([
                'cover_thread_x' => DB::raw('cover_focal_x'),
                'cover_thread_y' => DB::raw('cover_focal_y'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            if (Schema::hasColumn('forum_posts', 'cover_thread_x')) {
                $table->dropColumn('cover_thread_x');
            }
            if (Schema::hasColumn('forum_posts', 'cover_thread_y')) {
                $table->dropColumn('cover_thread_y');
            }
        });
    }
};
