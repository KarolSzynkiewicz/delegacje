@props([
    'variant' => 'cards',
    'employees',
    'ticketCostsByEmployee' => [],
    'ticketsIncomplete' => false,
    'requireAttachment' => true,
    /** Nagłówek sekcji, np. „Bilety” (dworzec) lub „Bilety lotnicze” (lotnisko). */
    'sectionTitle' => 'Bilety',
    'currencies' => null,
    'wireKeyPrefix' => 'pt-ticket',
    /** Nazwa właściwości Livewire (np. ticketCostsByEmployee, toAirportPublicTicketCostsByEmployee). */
    'ticketCostsBindingKey' => 'ticketCostsByEmployee',
    /**
     * Opcjonalnie: płaska tablica uploadów [employee_id => TemporaryUploadedFile] w komponencie nadrzędnym.
     * Livewire niezawodnie wiąże pliki przy płytkim kluczu; głębokie „…amount.currency.attachment” bywają puste.
     */
    'attachmentFlatBindingKey' => null,
    'flatAttachmentUploads' => [],
])

@php
    use App\Support\PublicTransportTicketCosts;

    $variant = in_array($variant, ['cards', 'table'], true) ? $variant : 'cards';
    $currencyValues = $currencies === null
        ? ['PLN', 'EUR', 'USD']
        : collect($currencies)->map(function ($c) {
            return $c instanceof \BackedEnum ? $c->value : (string) $c;
        })->all();

    $frameStyle = $variant === 'cards'
        ? 'border-top: 1px solid rgba(255,255,255,0.08);'
        : '';
    if ($ticketsIncomplete) {
        $frameStyle .= ' border: 1px solid rgba(239,68,68,0.55) !important; background: rgba(239,68,68,0.07); box-shadow: 0 0 0 1px rgba(239,68,68,0.12);';
    }

    $rootClasses = 'logistics-pt-tickets logistics-pt-tickets--'.$variant;
    if ($variant === 'cards') {
        $rootClasses .= ' mt-3 pt-3 rounded-3 px-2 py-2 px-md-3 transition-all';
    }
@endphp

<div {{ $attributes->class($rootClasses) }} style="{{ $frameStyle }}">
    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <i class="bi bi-ticket-perforated {{ $ticketsIncomplete ? 'text-danger' : 'text-info' }}" style="font-size:0.9rem;"></i>
        @if($variant === 'table')
            <h6 class="mb-0 fw-semibold {{ $ticketsIncomplete ? 'text-danger' : '' }}">{{ $sectionTitle }}</h6>
        @else
            <span class="small fw-semibold {{ $ticketsIncomplete ? 'text-danger' : '' }}">{{ $sectionTitle }}</span>
        @endif
    </div>

    @if($variant === 'cards')
        <style>
            .logistics-pt-tickets--cards .pt-tickets-grid{
                display:grid;
                grid-template-columns: repeat(7, minmax(140px, 1fr));
                gap: 10px;
            }
            @media (max-width: 1400px){ .logistics-pt-tickets--cards .pt-tickets-grid{ grid-template-columns: repeat(5, minmax(140px, 1fr)); } }
            @media (max-width: 1100px){ .logistics-pt-tickets--cards .pt-tickets-grid{ grid-template-columns: repeat(3, minmax(160px, 1fr)); } }
            @media (max-width: 700px){ .logistics-pt-tickets--cards .pt-tickets-grid{ grid-template-columns: repeat(2, minmax(160px, 1fr)); } }

            .logistics-pt-tickets--cards .pt-ticket-card{
                background: rgba(255,255,255,0.04);
                border: 1px solid rgba(255,255,255,0.10);
                border-radius: 14px;
                padding: 10px;
                box-shadow: 0 10px 26px rgba(0,0,0,0.20);
                min-height: 110px;
            }
            .logistics-pt-tickets--cards .pt-ticket-card:hover{
                border-color: rgba(59,130,246,0.35);
                background: rgba(59,130,246,0.06);
            }
            .logistics-pt-tickets--cards .pt-ticket-card.pt-ticket-card--incomplete{
                border-color: rgba(239,68,68,0.55) !important;
                background: rgba(239,68,68,0.06) !important;
                box-shadow: 0 0 0 1px rgba(239,68,68,0.12);
            }
            .logistics-pt-tickets--cards .pt-ticket-icon{
                width: 28px; height: 28px;
                border-radius: 10px;
                display:flex; align-items:center; justify-content:center;
                background: rgba(34,211,238,0.10);
                border: 1px solid rgba(34,211,238,0.25);
                color: #67e8f9;
                flex: 0 0 auto;
            }
            .logistics-pt-tickets--cards .pt-ticket-employee-name{
                font-weight: 600;
                font-size: .78rem;
                display:flex;
                gap: 6px;
                align-items:center;
            }
            .logistics-pt-tickets--cards .pt-ticket-person-icon{ color: rgba(148,163,184,0.9); }
            .logistics-pt-tickets--cards .pt-ticket-form-row{ display:flex; gap: 8px; align-items: start; width: 100%; }
            .logistics-pt-tickets--cards .pt-ticket-amount,
            .logistics-pt-tickets--cards .pt-ticket-currency{ flex: 1 1 0; min-width: 0; }
            .logistics-pt-tickets--cards .pt-ticket-card .form-control-sm,
            .logistics-pt-tickets--cards .pt-ticket-card .form-select-sm{
                border-radius: 10px !important;
                font-size: .75rem !important;
                padding: 0.2rem 0.45rem !important;
            }
            .logistics-pt-tickets--cards .pt-ticket-file-input{
                position:absolute !important;
                left:-9999px !important;
                width:1px !important;
                height:1px !important;
                opacity:0 !important;
            }
            .logistics-pt-tickets--cards .pt-ticket-attach-btn{
                width: 30px; height: 30px;
                border-radius: 12px;
                display:flex; align-items:center; justify-content:center;
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255,255,255,0.12);
                color: rgba(148,163,184,0.95);
                cursor: pointer;
                transition: all .15s ease;
                flex: 0 0 auto;
            }
            .logistics-pt-tickets--cards .pt-ticket-attach-btn:hover{
                background: rgba(59,130,246,0.10);
                border-color: rgba(59,130,246,0.35);
                color: #93c5fd;
            }
            .logistics-pt-tickets--cards .pt-ticket-attach-btn.is-attached{
                background: rgba(16,185,129,0.12);
                border-color: rgba(16,185,129,0.35);
                color: #6ee7b7;
            }
        </style>

        <div class="pt-tickets-grid">
            @foreach($employees as $employee)
                @php
                    $ticket = $ticketCostsByEmployee[$employee->id] ?? [];
                    $flatUp = $attachmentFlatBindingKey
                        ? ($flatAttachmentUploads[$employee->id] ?? $flatAttachmentUploads[(string) $employee->id] ?? null)
                        : null;
                    $hasAttachment = ! empty($flatUp)
                        || ! empty($ticket['attachment'])
                        || ! empty($ticket['attachment_path'] ?? null);
                    $fileInputId = 'ticket-attachment-'.$wireKeyPrefix.'-'.$employee->id;
                    $ticketRowIncomplete = PublicTransportTicketCosts::isRowIncomplete($ticket, $requireAttachment);
                    $bind = $ticketCostsBindingKey;
                @endphp
                <div class="pt-ticket-card {{ $ticketRowIncomplete ? 'pt-ticket-card--incomplete' : '' }}" wire:key="{{ $wireKeyPrefix }}-{{ $employee->id }}">
                    <div class="pt-ticket-employee-name" title="{{ $employee->full_name }}">
                        <span class="pt-ticket-person-icon"><i class="bi bi-person-circle"></i></span>
                        <span class="text-truncate d-inline-block" style="max-width: 110px;">{{ $employee->full_name }}</span>
                    </div>

                    <div class="pt-ticket-form-row mt-2">
                        <div class="pt-ticket-amount">
                            <input type="number" step="0.01" min="0"
                                   wire:model.lazy="{{ $bind }}.{{ $employee->id }}.amount"
                                   class="form-control form-control-sm @error($bind.'.'.$employee->id.'.amount') is-invalid @enderror"
                                   placeholder="0.00">
                            @error($bind.'.'.$employee->id.'.amount')
                                <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="pt-ticket-currency">
                            <select wire:model.live="{{ $bind }}.{{ $employee->id }}.currency" class="form-select form-select-sm">
                                @foreach($currencyValues as $val)
                                    <option value="{{ $val }}">{{ $val }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($requireAttachment)
                        <div class="mt-2 d-flex align-items-center justify-content-between gap-2">
                            <label class="pt-ticket-attach-btn {{ $hasAttachment ? 'is-attached' : '' }}"
                                   for="{{ $fileInputId }}"
                                   title="{{ $hasAttachment ? 'Załącznik dodany (kliknij aby zmienić)' : 'Dodaj załącznik' }}">
                                <i class="bi bi-paperclip"></i>
                            </label>
                            <span class="small text-muted text-truncate" style="font-size:.72rem; max-width: 180px;">
                                @if($hasAttachment)
                                    Załącznik dodany
                                @else
                                    Dodaj bilet
                                @endif
                            </span>
                            <input id="{{ $fileInputId }}"
                                   type="file"
                                   @if(! empty($attachmentFlatBindingKey))
                                       wire:model.live="{{ $attachmentFlatBindingKey }}.{{ $employee->id }}"
                                   @else
                                       wire:model="{{ $bind }}.{{ $employee->id }}.attachment"
                                   @endif
                                   class="pt-ticket-file-input">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <style>
            .logistics-pt-tickets--table tr.pt-ticket-row--incomplete td {
                background: rgba(239,68,68,0.06);
            }
            .logistics-pt-tickets--table .pt-ticket-file-input{
                position:absolute !important;
                left:-9999px !important;
                width:1px !important;
                height:1px !important;
                opacity:0 !important;
            }
            .logistics-pt-tickets--table .pt-ticket-attach-btn{
                width: 30px; height: 30px;
                border-radius: 12px;
                display:inline-flex; align-items:center; justify-content:center;
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255,255,255,0.12);
                color: rgba(148,163,184,0.95);
                cursor: pointer;
                transition: all .15s ease;
                flex: 0 0 auto;
            }
            .logistics-pt-tickets--table .pt-ticket-attach-btn:hover{
                background: rgba(59,130,246,0.10);
                border-color: rgba(59,130,246,0.35);
                color: #93c5fd;
            }
            .logistics-pt-tickets--table .pt-ticket-attach-btn.is-attached{
                background: rgba(16,185,129,0.12);
                border-color: rgba(16,185,129,0.35);
                color: #6ee7b7;
            }
        </style>
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size: 0.875rem;">
                <thead>
                    <tr class="text-muted" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: .03em;">
                        <th class="fw-semibold ps-0">Pracownik</th>
                        <th class="fw-semibold">Kwota</th>
                        <th class="fw-semibold">Waluta</th>
                        @if($requireAttachment)
                            <th class="fw-semibold text-end" style="width: 1%; white-space: nowrap;">Załącznik</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        @php
                            $bind = $ticketCostsBindingKey;
                            $ticket = $ticketCostsByEmployee[$employee->id] ?? [];
                            $flatUp = $attachmentFlatBindingKey
                                ? ($flatAttachmentUploads[$employee->id] ?? $flatAttachmentUploads[(string) $employee->id] ?? null)
                                : null;
                            $hasAttachment = ! empty($flatUp)
                                || ! empty($ticket['attachment'])
                                || ! empty($ticket['attachment_path'] ?? null);
                            $fileInputId = 'rtp-ticket-attachment-'.$wireKeyPrefix.'-'.$employee->id;
                            $ticketRowIncomplete = PublicTransportTicketCosts::isRowIncomplete($ticket, $requireAttachment);
                        @endphp
                        <tr wire:key="{{ $wireKeyPrefix }}-{{ $employee->id }}" class="{{ $ticketRowIncomplete ? 'pt-ticket-row--incomplete' : '' }}">
                            <td class="ps-0 align-middle">
                                <div class="d-flex align-items-center gap-2">
                                    @if($employee->image_url)
                                        <img src="{{ $employee->image_url }}" class="rtp-avatar" style="width:28px;height:28px;" alt="">
                                    @else
                                        <div class="rtp-avatar-placeholder" style="width:28px;height:28px;font-size:0.65rem;">
                                            {{ mb_strtoupper(mb_substr($employee->first_name, 0, 1).mb_substr($employee->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="fw-semibold">{{ $employee->full_name }}</span>
                                </div>
                            </td>
                            <td class="align-middle" style="min-width: 110px;">
                                <input type="number" step="0.01" min="0"
                                       wire:model.lazy="{{ $bind }}.{{ $employee->id }}.amount"
                                       class="form-control form-control-sm @error($bind.'.'.$employee->id.'.amount') is-invalid @enderror"
                                       placeholder="0.00">
                                @error($bind.'.'.$employee->id.'.amount')
                                    <div class="invalid-feedback" style="font-size:0.72rem;">{{ $message }}</div>
                                @enderror
                            </td>
                            <td class="align-middle" style="min-width: 90px;">
                                <select wire:model.live="{{ $bind }}.{{ $employee->id }}.currency"
                                        class="form-select form-select-sm">
                                    @foreach($currencyValues as $val)
                                        <option value="{{ $val }}">{{ $val }}</option>
                                    @endforeach
                                </select>
                            </td>
                            @if($requireAttachment)
                                <td class="align-middle text-end" style="vertical-align: middle;">
                                    <div class="d-inline-flex align-items-center gap-1 justify-content-end">
                                        <label class="pt-ticket-attach-btn {{ $hasAttachment ? 'is-attached' : '' }} mb-0"
                                               for="{{ $fileInputId }}"
                                               title="{{ $hasAttachment ? 'Załącznik dodany (kliknij aby zmienić)' : 'Dodaj załącznik' }}">
                                            <i class="bi bi-paperclip"></i>
                                        </label>
                                        <input id="{{ $fileInputId }}"
                                               type="file"
                                               @if(! empty($attachmentFlatBindingKey))
                                                   wire:model.live="{{ $attachmentFlatBindingKey }}.{{ $employee->id }}"
                                               @else
                                                   wire:model="{{ $bind }}.{{ $employee->id }}.attachment"
                                               @endif
                                               class="pt-ticket-file-input">
                                    </div>
                                    @error($bind.'.'.$employee->id.'.attachment')
                                        <div class="text-danger small mt-1 mb-0" style="font-size:0.7rem;">{{ $message }}</div>
                                    @enderror
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
