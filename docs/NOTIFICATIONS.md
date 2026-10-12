# Powiadomienia

Inbox (dzwonek) to kanał Laravel `database`. Push przeglądarki to kanał
`NotificationChannels\WebPush\WebPushChannel`. Wybór kanału idzie przez
`NotificationRouter` + katalog `NotificationEvent`. Mail, WhatsApp i SMS
zostają w katalogu, ale nie są wysyłane.

## Warstwy

```
fakt domenowy (Event)     listener              powiadomienie           kanał
─────────────────────     ────────              ─────────────           ─────
TaskAssigneeChanged    →  SendTaskAssigned   →  TaskAssigned         →  database
CommentPosted          →  SendComment…       →  CommentMentioned /
                                                TaskCommentAdded
ApprovalAssigneeChanged→  SendApprovalRequested
…
```

Kontroler / Livewire / MCP **zapisuje model**. Observer albo serwis emituje
event. Jedyny `->notify()` w aplikacji jest w `App\Notifications\Notifier`.

Preferencje: Profil → macierz (dzwonek i push). Brak wiersza w
`notification_preferences` = default z katalogu. Push dociera tylko do
przeglądarek, które zapisały subskrypcję (przycisk nad macierzą). Klucze
VAPID: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`.

## Typy (`data.type` / `NotificationEvent`)

| Typ | Kiedy | Odbiorca |
|-----|--------|----------|
| `task_assigned` | Zmiana `assigned_to` na zadaniu, podzadaniu albo wzmiance `@x!` | Nowy przypisany (nie aktor) |
| `comment_mentioned` | `@Nazwa` w komentarzu | Wspomniani (nie autor) |
| `task_comment_added` | Komentarz przy `ProjectTask` | Assignee, jeśli nie dostał wzmianki |
| `approval_requested` | Wniosek `@x?` albo zmiana zatwierdzającego | Zatwierdzający |
| `approval_decided` | Decyzja we wniosku | Autor wniosku |
| `mention_completed` | Odhaczenie wzmianki `@x!` | Autor wzmianki |
| `comment_liked` | Polubienie komentarza | Autor komentarza |
| `procedure_wait_elapsed` | Minął wait w procedurze | Assignee kroku i karty |
| `procedure_step_ready` | Wejście w krok innej osoby | Assignee kroku |
| `meeting_invited` | Nowy wpis w `participant_ids` | Uczestnik (nie aktor, nie assignee karty) |
| `block_reminder` | 15 minut przed blokiem z godziną, albo o godzinie z profilu przy bloku na cały dzień i przed startem spotkania | Właściciel bloku; przy spotkaniu assignee i uczestnicy |
| `deadline_reminder` | W dniu `due_at`, o godzinie z profilu (domyślnie 9:00), dopóki zadanie jest otwarte | Assignee. Spotkania pomijane |

Dzwonek przy slocie w kolumnie „W kalendarzu” jest włączony domyślnie.
Drugi klik go gasi i chowa; najechanie na wiersz pokazuje go z powrotem.
Zgaszony dzwonek nie wysyła nic, nawet gdy macierz ma oba kanały.
Godzina terminu i bloku na cały dzień: Profil → Powiadomienia.

Komenda `reminders:dispatch` chodzi co minutę obok `procedures:resume-waits`.
Po wysłaniu zapisuje `reminder_sent_for` albo `deadline_reminded_on`, więc
przesunięcie bloku albo terminu wysyła przypomnienie jeszcze raz. Po północy
zaległy termin milczy.

## Rozszerzanie (nowy fakt, np. wyjazd)

1. Event domenowy (`DepartureCreated`) + `event(...)` w serwisie po zapisie.
2. Listener `SendDepartureNotification` w `EventServiceProvider`.
3. Klasa `extends ChronoNotification` z `event(): NotificationEvent::…` i `toDatabase()`.
4. Case w `NotificationEvent` (label, grupa, defaulty kanałów, ikona).
5. Macierz w profilu dokłada wiersz sama.

Kolejny kanał: driver w `NotificationChannel::implemented()` + metoda
`toMail()` / odpowiednik na klasach. `toWebPush()` jest wspólne w
`ChronoNotification` i bierze treść z `toDatabase()`. Call site’ów zapisu
nie ruszasz.

## Kolejka

Klasy `ChronoNotification` implementują `ShouldQueue`. Testy i `.env.example`
zostają na `QUEUE_CONNECTION=sync`. W Sail jest serwis `queue`
(`php artisan queue:work`). Produkcja: `database` albo `redis` + worker.

## Dzwonek

Otwarcie listy **nie** oznacza wszystkiego przeczytanym. Klik w wpis idzie na
`notifications.open` (jedno jako przeczytane + redirect do karty).
„Oznacz przeczytane” jest osobnym przyciskiem.
