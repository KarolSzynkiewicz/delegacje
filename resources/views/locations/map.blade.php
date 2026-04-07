<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Mapa lokalizacji">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('locations.index') }}"
                    action="back"
                >
                    Lista
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="" />

    <div class="d-flex flex-column" style="height: calc(100vh - 180px);">
        {{-- Legend --}}
        <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-semibold small me-1">Filtruj:</span>
            @foreach($purposeTypes as $pt)
                <button
                    class="btn btn-sm purpose-filter active"
                    data-purpose="{{ $pt->value }}"
                    style="border: 2px solid var(--pin-{{ $pt->value }}); background: var(--pin-{{ $pt->value }}); color: #fff;"
                >
                    {{ $pt->label() }}
                </button>
            @endforeach
            <button class="btn btn-sm btn-outline-secondary purpose-filter active" data-purpose="none">
                Bez typu
            </button>
        </div>

        <div id="locations-map" class="flex-grow-1 rounded shadow-sm border"></div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    <script>
    (function () {
        const locations = @json($locations);

        const purposeColors = {
            workshop: '#6c757d',
            project:  '#0d6efd',
            quarter:  '#0dcaf0',
            airport:  '#6610f2',
            base:     '#198754',
            other:    '#ffc107',
            none:     '#adb5bd',
        };

        document.documentElement.style.setProperty('--pin-workshop', purposeColors.workshop);
        document.documentElement.style.setProperty('--pin-project',  purposeColors.project);
        document.documentElement.style.setProperty('--pin-quarter',  purposeColors.quarter);
        document.documentElement.style.setProperty('--pin-airport',  purposeColors.airport);
        document.documentElement.style.setProperty('--pin-base',     purposeColors.base);
        document.documentElement.style.setProperty('--pin-other',    purposeColors.other);

        function markerIcon(color) {
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="40" viewBox="0 0 28 40">
                <path d="M14 0C6.27 0 0 6.27 0 14c0 10.5 14 26 14 26s14-15.5 14-26C28 6.27 21.73 0 14 0z" fill="${color}" stroke="#fff" stroke-width="1.5"/>
                <circle cx="14" cy="14" r="6" fill="#fff" opacity="0.9"/>
            </svg>`;
            return L.icon({
                iconUrl: 'data:image/svg+xml;base64,' + btoa(svg),
                iconSize: [28, 40],
                iconAnchor: [14, 40],
                popupAnchor: [0, -36],
            });
        }

        const map = L.map('locations-map').setView([51.0, 10.5], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        const markers = [];

        locations.forEach(loc => {
            const purposes = loc.purposes.length > 0 ? loc.purposes : ['none'];
            const primaryPurpose = purposes[0];
            const color = purposeColors[primaryPurpose] || purposeColors.none;

            const badgesHtml = purposes.map(p => {
                const c = purposeColors[p] || purposeColors.none;
                return `<span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:11px;color:#fff;background:${c};margin-right:3px;">${p}</span>`;
            }).join('');

            const popup = `
                <div style="min-width:180px">
                    <strong><a href="${loc.url}">${loc.name}</a></strong><br>
                    <span style="color:#666;font-size:12px;">${loc.address || ''}</span><br>
                    <div style="margin-top:4px">${badgesHtml}</div>
                </div>`;

            const marker = L.marker([loc.lat, loc.lng], { icon: markerIcon(color) })
                .bindPopup(popup);

            marker._locPurposes = purposes;
            markers.push(marker);
            marker.addTo(map);
        });

        if (markers.length > 0) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.15));
        }

        const activeFilters = new Set([
            ...@json(collect($purposeTypes)->pluck('value')),
            'none'
        ]);

        document.querySelectorAll('.purpose-filter').forEach(btn => {
            btn.addEventListener('click', function () {
                const purpose = this.dataset.purpose;
                if (activeFilters.has(purpose)) {
                    activeFilters.delete(purpose);
                    this.classList.remove('active');
                    this.style.opacity = '0.4';
                } else {
                    activeFilters.add(purpose);
                    this.classList.add('active');
                    this.style.opacity = '1';
                }

                markers.forEach(m => {
                    const visible = m._locPurposes.some(p => activeFilters.has(p));
                    if (visible && !map.hasLayer(m)) map.addLayer(m);
                    if (!visible && map.hasLayer(m)) map.removeLayer(m);
                });
            });
        });
    })();
    </script>
    @endpush
</x-app-layout>
