# Powiadomienia w aplikacji (dzwonek w navbarze)

Powiadomienia są zapisywane w tabeli `notifications` (kanał Laravel `database`) i wyświetlane w dropdownie obok menu użytkownika. Przy otwarciu listy nieprzeczytane wpisy są oznaczane jako przeczytane (ikonka dzwonka przestaje świecić).

## Typy powiadomień

| Typ (`data.type`) | Kiedy | Odbiorca |
|-------------------|--------|----------|
| `task_assigned` | Utworzenie zadania lub zmiana osoby w polu „przypisany” | Nowy przypisany użytkownik (nie autor zmiany) |
| `task_comment_added` | Nowy komentarz przy **zadaniu** (`ProjectTask`) | Użytkownik przypisany do zadania — **bez konieczności @wzmianki**. Nie wysyłamy, jeśli komentarz dodał sam przypisany lub jeśli przypisany już dostał powiadomienie `comment_mentioned` w tym samym komentarzu. |
| `comment_mentioned` | W treści komentarza występuje `@NazwaUżytkownika` (nazwa jak w bazie `users.name`, także gdy jest to adres e-mail) | Wspomniani użytkownicy (nie autor) |
| `subtask_mentioned` | W **nazwie podzadania** (dodanie lub edycja) jest @wzmianka | Wspomniani użytkownicy (nie autor) |

## Gdzie działa @wzmianka

- **Komentarze** (wszystkie miejsca używające komponentu `x-comments`): autocomplete po `@`, ten sam regex co po stronie serwera (`UserMentionService::MENTION_REGEX`).
- **Podzadania** (Livewire `TaskSubtasks` na widoku zadania): to samo przy dodawaniu i edycji nazwy; w liście podzadań fragmenty `@…` są podświetlone na niebiesko.

## Podzadania — autocomplete @ (UI)

Logika Alpine jest w **`resources/js/app.js`** jako `window.taskSubtaskMentionLine` (musi być dostępna przed inicjalizacją Alpine; skrypt z `@script` w komponencie Livewire uruchamiał się za późno).

Pola używają **`wire:model.defer`**, nie `wire:model.live`: przy `.live` każdy znak wysyła żądanie do serwera i Livewire **przerysowuje** komponent, co zrywa stan Alpine i znika lista podpowiedzi.

Po zmianach w JS: `npm run build` lub `npm run dev`.

## Rozszerzanie

1. Nowa klasa w `App\Notifications` z `via(['database'])` i `toDatabase()` zwracającym m.in. pole `type` oraz `task_url` lub `url` i sensowny tekst linku (`task_name`, `context_name` albo `subtask_name`).
2. Wywołanie `$user->notify(new …)` z kontrolera, Livewire lub listenera.
3. W `resources/views/livewire/notification-bell.blade.php` dodać ikonę dla nowego `type` (sekcja `@class` przy `bi …`).

Wspólna logika wyciągania @z tekstu: `App\Services\UserMentionService` (`extractHandles`, `notifyCommentMentions`, `notifySubtaskMentions`).

## Linki w dropdownie

Skrót **„Moje zadania”** prowadzi do listy zadań z filtrem `?myTasksOnly=true` (`route('tasks.index', ['myTasksOnly' => 'true'])`).

## Migracja

Tabela: `php artisan migrate` (migracja `*_create_notifications_table`).
