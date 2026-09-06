<?php

namespace Database\Seeders;

use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class ForumPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->orderBy('id')->first();
        if (! $author) {
            return;
        }

        if (ForumPost::query()->exists()) {
            return;
        }

        $post = ForumPost::query()->create([
            'user_id' => $author->id,
            'title' => 'Jak działają nowe wyjazdy',
            'body' => [
                ['type' => 'text', 'content' => "Wyjazd to jeden rekord logistyczny: data, trasa i lista osób. Nie składasz już projektu, auta i noclegu obok siebie — planer spina to w jedną całość.\n\nKogoś dopisujesz tylko wtedy, gdy w dniu startu jest w bazie. Jeśli system widzi osobę na projekcie albo w drodze, nie puści jej do tego wyjazdu.\n\nPlaner idzie trzema krokami. Najpierw projekt i role, potem nocleg, na końcu auto albo koszt biletu — gdy jedziecie komunikacją, waluta ma mieć trzy znaki.\n\nPrzy edycji obsady nagłówka nie ruszasz: daty, auto i trasa to zmiana na samym wyjeździe, nie przy dopisywaniu ludzi."],
            ],
            'pinned' => true,
        ]);
        $post->syncTagsFromString('logistyka, wyjazdy, instrukcja');
    }
}
