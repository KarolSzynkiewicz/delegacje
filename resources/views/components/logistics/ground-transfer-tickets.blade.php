@props([
    'rows' => [],
    'title' => 'Bilety — transfer (transport publiczny na odcinku ziemnym)',
])

@php
    use App\Support\PublicDiskFileUrl;
@endphp

@if(is_array($rows) && count($rows) > 0)
    <div class="ground-transfer-tickets mt-3 pt-3 border-top" style="border-color: rgba(255,255,255,0.08) !important;">
        <h6 class="fw-semibold mb-3 small text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: .05em;">
            <i class="bi bi-ticket-perforated me-1"></i>{{ $title }}
        </h6>
        <div class="table-responsive rounded-3 border" style="border-color: rgba(255,255,255,0.08) !important;">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light" style="--bs-table-bg: rgba(255,255,255,0.04);">
                    <tr class="text-muted small">
                        <th class="ps-3 py-2">Odcinek</th>
                        <th class="py-2">Osoba</th>
                        <th class="py-2">Kwota</th>
                        <th class="pe-3 py-2 text-end">Załącznik</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $href = PublicDiskFileUrl::url($row['attachment_path'] ?? null);
                        @endphp
                        <tr>
                            <td class="ps-3">{{ $row['leg_label'] ?? '—' }}</td>
                            <td>{{ $row['employee_name'] ?? ('#' . ($row['employee_id'] ?? '')) }}</td>
                            <td>
                                @if(isset($row['amount']) && $row['amount'] !== null)
                                    {{ number_format((float) $row['amount'], 2) }} {{ $row['currency'] ?? 'PLN' }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="pe-3 text-end">
                                @if($href)
                                    <a href="{{ $href }}" target="_blank" rel="noopener" class="text-decoration-none">
                                        <i class="bi bi-paperclip"></i> Podgląd
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
