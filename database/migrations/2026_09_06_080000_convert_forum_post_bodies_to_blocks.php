<?php

use App\Models\ForumPost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('forum_posts')->select('id', 'body')->orderBy('id')->each(function (object $row): void {
            $decoded = json_decode((string) $row->body, true);
            if (is_array($decoded)) {
                return;
            }

            $blocks = ForumPost::normalizeBlocks((string) $row->body);
            if ($blocks === []) {
                $blocks = [['type' => 'text', 'content' => (string) $row->body]];
            }

            DB::table('forum_posts')->where('id', $row->id)->update([
                'body' => json_encode($blocks, JSON_UNESCAPED_UNICODE),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('forum_posts')->select('id', 'body')->orderBy('id')->each(function (object $row): void {
            $blocks = json_decode((string) $row->body, true);
            if (! is_array($blocks)) {
                return;
            }

            $text = collect($blocks)
                ->where('type', 'text')
                ->pluck('content')
                ->filter()
                ->implode("\n\n");

            DB::table('forum_posts')->where('id', $row->id)->update([
                'body' => $text,
            ]);
        });
    }
};
