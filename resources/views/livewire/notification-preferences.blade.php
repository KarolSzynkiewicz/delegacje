<x-ui.card>
    <div class="p-4 p-md-4 np-grid">
        <div class="np-title">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
                <h3 class="fs-5 fw-semibold mb-0">Powiadomienia</h3>
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="sendTestPush" wire:loading.attr="disabled" wire:target="sendTestPush">
                    Wyślij próbę
                </button>
            </div>
            <p class="text-muted small mb-0">Wybierz, gdzie chcesz dostać powiadomienia.</p>
            @if($testPush === 'sent')
                <p class="small text-success mb-0 mt-2">Wysłane na zapisane urządzenia. Okienko systemowe powinno wyskoczyć za chwilę. Klik otwiera profil.</p>
            @elseif($testPush === 'none')
                <p class="small text-warning mb-0 mt-2">Najpierw włącz powiadomienia w tej przeglądarce. Próba idzie tylko na zapisane urządzenia.</p>
            @elseif($testPush === 'failed')
                <p class="small text-danger mb-0 mt-2">Serwer nie dosłał pusha. Sprawdź klucze VAPID.</p>
            @endif
        </div>

        @foreach($channels as $channel)
            <div class="np-colhead">
                <div class="np-colhead__name">
                    <i class="bi {{ $channel->icon() }}" aria-hidden="true"></i>
                    <span>{{ $channel->columnLabel() }}</span>
                </div>
                <label class="np-colhead__all">
                    <input
                        type="checkbox"
                        wire:key="np-all-{{ $channel->value }}-{{ $columnStates[$channel->value] }}"
                        @checked($columnStates[$channel->value] === 'all')
                        x-data
                        x-init="$el.indeterminate = @js($columnStates[$channel->value] === 'some')"
                        wire:click.prevent="toggleColumn('{{ $channel->value }}')"
                        aria-label="Zaznacz wszystkie: {{ $channel->columnLabel() }}"
                    >
                    <span>zaznacz wszystkie</span>
                </label>
            </div>
        @endforeach

        <div class="np-clock-block">
            <label class="np-clock">
                <span>Przypomnienie o terminie</span>
                <input
                    type="time"
                    class="np-clock__input"
                    wire:model.live="deadlineReminderTime"
                    aria-label="Godzina przypomnienia o terminie"
                >
            </label>
            <p class="np-clock__hint">Domyślnie o 9:00, w dniu terminu, tylko gdy zadanie jest otwarte. Blok na cały dzień budzi o tej samej godzinie.</p>
        </div>

        <div
            class="np-device"
            wire:ignore
            x-data="notificationDevice(@js($vapidPublicKey))"
            x-init="init()"
        >
            <p class="text-muted small mb-2" x-show="state === 'checking'" x-cloak>Sprawdzam tę przeglądarkę…</p>
            <p class="text-muted small mb-0" x-show="state === 'unsupported'" x-cloak>Ta przeglądarka nie obsługuje powiadomień push.</p>
            <p class="text-muted small mb-0" x-show="state === 'unconfigured'" x-cloak>Push nie jest jeszcze włączony na serwerze.</p>
            <p class="text-muted small mb-0" x-show="state === 'denied'" x-cloak>Powiadomienia są zablokowane w ustawieniach tej przeglądarki.</p>
            <p class="text-muted small mb-0" x-show="state === 'error'" x-cloak>Nie udało się włączyć powiadomień w tej przeglądarce.</p>
            <button
                type="button"
                class="btn btn-sm btn-primary"
                x-show="state === 'off'"
                x-cloak
                @click="enable()"
                :disabled="busy"
            >Włącz powiadomienia w tej przeglądarce</button>
            <div x-show="state === 'on'" x-cloak>
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <span class="small">Ta przeglądarka dostaje powiadomienia push.</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="disable()" :disabled="busy">Wyłącz tutaj</button>
                </div>
            </div>
            <p class="text-muted small mt-2 mb-0">Na iPhonie push działa po dodaniu strony do ekranu początkowego.</p>
        </div>

        @foreach($groups as $block)
            <div class="np-section">{{ $block['group']->label() }}</div>
            @foreach($block['events'] as $event)
                <div class="np-label">
                    <div>{{ $event->label() }}</div>
                    @if($event === \App\Enums\NotificationEvent::BlockReminder)
                        <div class="np-label__hint">15 minut przed startem. Wyłączony dzwonek przy bloku nic nie wyśle.</div>
                    @elseif($event === \App\Enums\NotificationEvent::DeadlineReminder)
                        <div class="np-label__hint">W wybranej godzinie. Kanały stąd, godzina z pola powyżej.</div>
                    @endif
                </div>
                @foreach($channels as $channel)
                    <div class="np-check" wire:key="np-{{ $event->value }}-{{ $channel->value }}-{{ (int) ($enabled[$event->value][$channel->value] ?? false) }}">
                        <input
                            type="checkbox"
                            wire:model.live="enabled.{{ $event->value }}.{{ $channel->value }}"
                            @checked($enabled[$event->value][$channel->value] ?? false)
                            aria-label="{{ $event->label() }} — {{ $channel->columnLabel() }}"
                        >
                    </div>
                @endforeach
            @endforeach
        @endforeach
    </div>
</x-ui.card>
