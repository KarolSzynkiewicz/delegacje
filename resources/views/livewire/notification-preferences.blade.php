<x-ui.card>
    <div class="p-4 p-md-4">
        <h3 class="fs-5 fw-semibold mb-1">Powiadomienia</h3>
        <p class="text-muted small mb-3">
            Dzwonek w aplikacji. Kolejne kanały (e-mail, push) dołączą do tej samej listy.
        </p>

        @foreach($groups as $block)
            <div @class(['mb-3' => ! $loop->last])>
                <div class="text-uppercase text-muted small fw-semibold mb-2" style="letter-spacing:.04em;">
                    {{ $block['group']->label() }}
                </div>
                <div class="d-flex flex-column gap-2">
                    @foreach($block['events'] as $event)
                        <label class="d-flex align-items-center justify-content-between gap-3 mb-0">
                            <span>{{ $event->label() }}</span>
                            <input
                                type="checkbox"
                                class="form-check-input m-0"
                                @checked($enabled[$event->value] ?? false)
                                wire:click="toggle('{{ $event->value }}')"
                            >
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-ui.card>
