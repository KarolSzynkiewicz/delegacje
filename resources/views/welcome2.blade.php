<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Komponenty UI - Przegląd">
        </x-ui.page-header>
    </x-slot>

    <div class="container-fluid">

        {{-- ══════════════════════════════════════════════════════════════
             🧪 PROBE — teal2 / xuiv2
             Testowy wariant stylu zainspirowany "chronologic-landing.html":
             tło (siatka + ziarno + poświata kursora), animacje hover
             (magnetyczne przyciski, tilt kart) i czcionki (Space Grotesk +
             JetBrains Mono). W pełni odizolowany od reszty /2 i aplikacji —
             nic poza tą sekcją się nie zmienia. Traktuj jak próbkę do oceny.
             ══════════════════════════════════════════════════════════════ --}}
        <div class="mb-5 pb-4 border-bottom border-secondary border-opacity-25">
            <h3 class="mb-1">🧪 Probe — <code>teal2</code> / <code>xuiv2</code> <span class="text-muted small fw-normal">(izolowana próbka, nie wpływa na resztę appki)</span></h3>
            <p class="text-muted small mb-3">
                Wariant komponentów <code>x-ui.*</code> „podwędzony” z Twojego <code>chronologic-landing.html</code>: tło (siatka + ziarno + poświata kursora podążająca za myszką), czcionki (<code>Space Grotesk</code> + <code>JetBrains Mono</code>), akcent teal/amber/cyan zamiast niebieskiego, magnetyczne przyciski i tilt kart pod kursorem. Wszystko zamknięte w klasie <code>.xuiv2-probe</code> — jeśli się spodoba, przeniesiemy to realnie do <code>x-ui.*</code>.
            </p>

            <div class="xuiv2-probe" id="xuiv2Probe">
                <div class="xuiv2-bg-grid"></div>
                <div class="xuiv2-grain"></div>
                <div class="xuiv2-cursor-glow" id="xuiv2CursorGlow"></div>

                <div class="xuiv2-stage">
                    <span class="xuiv2-eyebrow">Design probe · v2</span>
                    <h2 class="xuiv2-h2">Ten sam system, <span class="xuiv2-accent">inny nastrój.</span></h2>
                    <p class="xuiv2-sub">Teal zamiast niebieskiego, mono zamiast grotesque'u w danych, siatka i ziarno w tle zamiast płaskiej czerni. Poniżej te same komponenty <code>x-ui.*</code> w nowej skórce.</p>

                    {{-- Przyciski (magnetyczne) --}}
                    <div class="xuiv2-row">
                        <button type="button" class="xuiv2-btn xuiv2-btn--primary xuiv2-magnetic">Zobacz jak to działa</button>
                        <button type="button" class="xuiv2-btn xuiv2-btn--ghost xuiv2-magnetic">x-ui.button ghost</button>
                        <button type="button" class="xuiv2-btn xuiv2-btn--ghost xuiv2-magnetic" disabled>Wyłączony</button>
                    </div>

                    {{-- Badge'e --}}
                    <div class="xuiv2-row mt-3">
                        <span class="xuiv2-badge xuiv2-badge--teal">Aktywne</span>
                        <span class="xuiv2-badge xuiv2-badge--amber">Uwaga</span>
                        <span class="xuiv2-badge xuiv2-badge--cyan">Info</span>
                        <span class="xuiv2-badge xuiv2-badge--muted">Zamknięte</span>
                    </div>

                    {{-- Karty (tilt pod kursorem) --}}
                    <div class="xuiv2-grid mt-4">
                        <div class="xuiv2-card xuiv2-tilt">
                            <span class="xuiv2-card-num">01</span>
                            <h3>x-ui.card</h3>
                            <p>Ta sama treść co dziś, ale hairline-grid obwódka i akcent na górnej krawędzi zamiast glassmorphic blura.</p>
                        </div>
                        <div class="xuiv2-card xuiv2-tilt xuiv2-card--featured">
                            <span class="xuiv2-featured-badge">Wyróżniona</span>
                            <span class="xuiv2-card-num">02</span>
                            <h3>x-ui.page-header</h3>
                            <p>Kicker + tytuł w Space Grotesk, dane poniżej w JetBrains Mono — ta sama hierarchia co w landingu.</p>
                        </div>
                        <div class="xuiv2-card xuiv2-tilt">
                            <span class="xuiv2-card-num">03</span>
                            <h3>x-ui.badge / input</h3>
                            <p>Formularze i etykiety zachowują dark theme, ale z cieńszą, bardziej „redakcyjną” obwódką.</p>
                        </div>
                    </div>

                    {{-- Stat band (mono) --}}
                    <div class="xuiv2-stats mt-4">
                        <div class="xuiv2-stat"><div class="xuiv2-stat-num" data-xuiv2-count="24">0</div><div class="xuiv2-stat-label">aktywne zadania</div></div>
                        <div class="xuiv2-stat"><div class="xuiv2-stat-num" data-xuiv2-count="6">0</div><div class="xuiv2-stat-label">moduły systemu</div></div>
                        <div class="xuiv2-stat"><div class="xuiv2-stat-num" data-xuiv2-count="3">0</div><div class="xuiv2-stat-label">sprinty w toku</div></div>
                        <div class="xuiv2-stat"><div class="xuiv2-stat-num" data-xuiv2-count="0">0</div><div class="xuiv2-stat-label">arkuszy potrzebnych</div></div>
                    </div>

                    {{-- Mono data-plate (tabela) --}}
                    <div class="xuiv2-plate mt-4">
                        <div class="xuiv2-plate-row xuiv2-plate-head">
                            <span>Zadanie</span><span>Status</span><span>Termin</span>
                        </div>
                        <div class="xuiv2-plate-row">
                            <span>Rozliczenie delegacji #482</span><span class="xuiv2-mono xuiv2-dot xuiv2-dot--teal">Ukończone</span><span class="xuiv2-mono">18.08.2026</span>
                        </div>
                        <div class="xuiv2-plate-row">
                            <span>Przegląd floty — Q3</span><span class="xuiv2-mono xuiv2-dot xuiv2-dot--amber">W trakcie</span><span class="xuiv2-mono">25.08.2026</span>
                        </div>
                        <div class="xuiv2-plate-row">
                            <span>Onboarding: 3 kandydatów</span><span class="xuiv2-mono xuiv2-dot xuiv2-dot--cyan">Oczekujące</span><span class="xuiv2-mono">02.09.2026</span>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                #xuiv2Probe.xuiv2-probe { --xv2-anim: 1; }
                @media (prefers-reduced-motion: reduce) {
                    #xuiv2Probe.xuiv2-probe { --xv2-anim: 0; }
                }

                @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&family=JetBrains+Mono:wght@400;500;600;700&display=swap');

                .xuiv2-probe {
                    --xv2-bg: #070a13;
                    --xv2-surface: #0e1421;
                    --xv2-surface-2: #131b2c;
                    --xv2-line: #1e2740;
                    --xv2-teal: #2dd4bf;
                    --xv2-teal-dim: #1a3d38;
                    --xv2-amber: #fbbf24;
                    --xv2-cyan: #22d3ee;
                    --xv2-text: #e8ecf5;
                    --xv2-muted: #7c869e;
                    --xv2-faint: #4a5268;
                    --xv2-display: 'Space Grotesk', sans-serif;
                    --xv2-mono: 'JetBrains Mono', ui-monospace, monospace;

                    position: relative;
                    isolation: isolate;
                    overflow: hidden;
                    border-radius: 16px;
                    border: 1px solid var(--xv2-line);
                    background: var(--xv2-bg);
                    padding: 48px 40px;
                }
                .xuiv2-probe *,
                .xuiv2-probe *::before,
                .xuiv2-probe *::after {
                    box-sizing: border-box;
                    font-family: var(--xv2-display);
                    color: var(--xv2-text);
                }
                .xuiv2-probe code { font-family: var(--xv2-mono); }

                .xuiv2-bg-grid {
                    position: absolute; inset: 0; z-index: 0; pointer-events: none;
                    background-image:
                        linear-gradient(to right, rgba(45,212,191,0.06) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(45,212,191,0.06) 1px, transparent 1px);
                    background-size: 42px 42px;
                    mask-image: radial-gradient(ellipse 90% 70% at 50% 0%, black 40%, transparent 100%);
                }
                .xuiv2-grain {
                    position: absolute; inset: 0; z-index: 0; pointer-events: none; opacity: .05; mix-blend-mode: overlay;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
                }
                .xuiv2-cursor-glow {
                    position: absolute; top: 0; left: 0; width: 380px; height: 380px; border-radius: 50%;
                    background: radial-gradient(circle, rgba(45,212,191,0.16) 0%, transparent 70%);
                    pointer-events: none; z-index: 1; transform: translate(-50%,-50%);
                    mix-blend-mode: screen; opacity: 0; transition: opacity .3s ease;
                }
                .xuiv2-probe:hover .xuiv2-cursor-glow { opacity: 1; }
                @media (pointer: coarse) { .xuiv2-cursor-glow { display: none; } }

                .xuiv2-stage { position: relative; z-index: 2; max-width: 760px; }
                .xuiv2-eyebrow {
                    font-family: var(--xv2-mono); font-size: 11.5px; letter-spacing: .14em; text-transform: uppercase;
                    color: var(--xv2-teal); display: inline-flex; align-items: center; gap: 10px; margin-bottom: 16px;
                }
                .xuiv2-eyebrow::before { content: ''; width: 22px; height: 1px; background: var(--xv2-teal); }
                .xuiv2-h2 { font-size: clamp(26px, 3vw, 36px); font-weight: 600; letter-spacing: -0.01em; line-height: 1.15; margin: 0 0 14px; }
                .xuiv2-accent { color: var(--xv2-teal); font-style: italic; font-weight: 400; }
                .xuiv2-sub { font-size: 15px; line-height: 1.6; color: var(--xv2-muted) !important; max-width: 560px; margin: 0 0 28px; }
                .xuiv2-sub code { color: var(--xv2-cyan) !important; }

                .xuiv2-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }

                .xuiv2-btn {
                    font-family: var(--xv2-mono) !important; font-size: 13px; font-weight: 500;
                    padding: 11px 22px; border-radius: 7px; cursor: pointer; border: 1px solid transparent;
                    will-change: transform; transition: box-shadow .2s ease, border-color .2s ease, opacity .2s ease;
                }
                .xuiv2-btn--primary { background: var(--xv2-teal); color: #04110f !important; }
                .xuiv2-btn--primary:hover { box-shadow: 0 8px 24px rgba(45,212,191,0.32); }
                .xuiv2-btn--ghost { background: transparent; border-color: var(--xv2-line); }
                .xuiv2-btn--ghost:hover { border-color: var(--xv2-teal); color: var(--xv2-teal) !important; }
                .xuiv2-btn:disabled { opacity: .35; cursor: not-allowed; }

                .xuiv2-badge {
                    font-family: var(--xv2-mono) !important; font-size: 11px; font-weight: 600; letter-spacing: .02em;
                    padding: 5px 12px; border-radius: 20px; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 6px;
                }
                .xuiv2-badge--teal  { background: rgba(45,212,191,.12); color: var(--xv2-teal) !important; border-color: rgba(45,212,191,.3); }
                .xuiv2-badge--amber { background: rgba(251,191,36,.12); color: var(--xv2-amber) !important; border-color: rgba(251,191,36,.3); }
                .xuiv2-badge--cyan  { background: rgba(34,211,238,.12); color: var(--xv2-cyan) !important; border-color: rgba(34,211,238,.3); }
                .xuiv2-badge--muted { background: rgba(124,134,158,.12); color: var(--xv2-muted) !important; border-color: rgba(124,134,158,.3); }

                .xuiv2-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--xv2-line); border: 1px solid var(--xv2-line); border-radius: 12px; overflow: hidden; }
                @media (max-width: 900px) { .xuiv2-grid { grid-template-columns: 1fr; } }
                .xuiv2-card {
                    background: var(--xv2-surface); padding: 26px 22px; position: relative; overflow: hidden;
                    transition: background .3s ease, transform .12s ease; transform-style: preserve-3d; will-change: transform;
                }
                .xuiv2-card:hover { background: var(--xv2-surface-2); }
                .xuiv2-card::before {
                    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--xv2-teal);
                    transform: scaleX(0); transform-origin: left; transition: transform .4s ease;
                }
                .xuiv2-card:hover::before { transform: scaleX(1); }
                .xuiv2-card--featured { background: var(--xv2-surface-2); }
                .xuiv2-card--featured::before { transform: scaleX(1); }
                .xuiv2-featured-badge {
                    position: absolute; top: 12px; right: 14px; background: var(--xv2-teal); color: #04110f !important;
                    font-family: var(--xv2-mono) !important; font-size: 9.5px; font-weight: 700; letter-spacing: .04em;
                    padding: 4px 9px; border-radius: 20px;
                }
                .xuiv2-card-num { font-family: var(--xv2-mono) !important; font-size: 11px; color: var(--xv2-faint) !important; }
                .xuiv2-card h3 { font-size: 17px; font-weight: 600; margin: 14px 0 8px; }
                .xuiv2-card p { font-size: 13px; color: var(--xv2-muted) !important; line-height: 1.55; margin: 0; }

                .xuiv2-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px; border-top: 1px solid var(--xv2-line); border-bottom: 1px solid var(--xv2-line); padding: 26px 0; }
                @media (max-width: 700px) { .xuiv2-stats { grid-template-columns: repeat(2, 1fr); row-gap: 20px; } }
                .xuiv2-stat-num { font-family: var(--xv2-mono) !important; font-size: clamp(26px, 3vw, 34px); font-weight: 700; color: var(--xv2-teal) !important; line-height: 1; }
                .xuiv2-stat-label { margin-top: 8px; font-size: 12.5px; color: var(--xv2-muted) !important; }

                .xuiv2-plate { border: 1px solid var(--xv2-line); border-radius: 10px; overflow: hidden; }
                .xuiv2-plate-row { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px; padding: 11px 16px; border-bottom: 1px solid var(--xv2-line); font-size: 13px; }
                .xuiv2-plate-row:last-child { border-bottom: none; }
                .xuiv2-plate-head { background: var(--xv2-surface-2); font-family: var(--xv2-mono) !important; font-size: 10.5px !important; text-transform: uppercase; letter-spacing: .08em; color: var(--xv2-faint) !important; }
                .xuiv2-mono { font-family: var(--xv2-mono) !important; font-size: 12.5px; }
                .xuiv2-dot { display: inline-flex; align-items: center; gap: 6px; }
                .xuiv2-dot::before { content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
                .xuiv2-dot--teal::before  { background: var(--xv2-teal); }
                .xuiv2-dot--amber::before { background: var(--xv2-amber); }
                .xuiv2-dot--cyan::before  { background: var(--xv2-cyan); }

                @media (prefers-reduced-motion: reduce) {
                    .xuiv2-card, .xuiv2-btn { transition: none !important; }
                }
            </style>

            <script>
                (function () {
                    var root = document.getElementById('xuiv2Probe');
                    if (!root || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                    // ── Poświata kursora ──
                    var glow = document.getElementById('xuiv2CursorGlow');
                    root.addEventListener('mousemove', function (e) {
                        var r = root.getBoundingClientRect();
                        glow.style.transform = 'translate(' + (e.clientX - r.left - 190) + 'px, ' + (e.clientY - r.top - 190) + 'px)';
                    });

                    // ── Magnetyczne przyciski ──
                    if (window.matchMedia('(pointer: fine)').matches) {
                        root.querySelectorAll('.xuiv2-magnetic').forEach(function (btn) {
                            btn.addEventListener('mousemove', function (e) {
                                var r = btn.getBoundingClientRect();
                                var x = (e.clientX - r.left - r.width / 2) * 0.35;
                                var y = (e.clientY - r.top - r.height / 2) * 0.35;
                                btn.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
                            });
                            btn.addEventListener('mouseleave', function () {
                                btn.style.transform = 'translate(0, 0)';
                                btn.style.transition = 'transform .4s cubic-bezier(.16,1,.3,1)';
                                setTimeout(function () { btn.style.transition = ''; }, 400);
                            });
                        });

                        // ── Tilt kart ──
                        root.querySelectorAll('.xuiv2-tilt').forEach(function (card) {
                            card.addEventListener('mousemove', function (e) {
                                var r = card.getBoundingClientRect();
                                var px = (e.clientX - r.left) / r.width - 0.5;
                                var py = (e.clientY - r.top) / r.height - 0.5;
                                card.style.transform = 'perspective(800px) rotateY(' + (px * 6) + 'deg) rotateX(' + (-py * 6) + 'deg)';
                            });
                            card.addEventListener('mouseleave', function () {
                                card.style.transform = 'perspective(800px) rotateY(0) rotateX(0)';
                                card.style.transition = 'transform .5s ease';
                                setTimeout(function () { card.style.transition = ''; }, 500);
                            });
                        });
                    }

                    // ── Liczniki statystyk (odpalane raz, na wejście w viewport) ──
                    var counted = false;
                    function runCounters() {
                        if (counted) return;
                        counted = true;
                        root.querySelectorAll('[data-xuiv2-count]').forEach(function (el) {
                            var target = parseInt(el.dataset.xuiv2Count, 10) || 0;
                            var start = null;
                            var duration = 900;
                            function step(ts) {
                                if (!start) start = ts;
                                var progress = Math.min((ts - start) / duration, 1);
                                el.textContent = Math.round(progress * target);
                                if (progress < 1) requestAnimationFrame(step);
                            }
                            requestAnimationFrame(step);
                        });
                    }
                    if ('IntersectionObserver' in window) {
                        var io = new IntersectionObserver(function (entries) {
                            entries.forEach(function (entry) { if (entry.isIntersecting) runCounters(); });
                        }, { threshold: .4 });
                        io.observe(root);
                    } else {
                        runCounters();
                    }
                })();
            </script>
        </div>

        {{-- Logistyka (DRY): wspólne widoki z planerami wyjazdu / zjazdu — źródło: resources/views/components/logistics/ --}}
        <div class="mb-5 pb-4 border-bottom border-secondary border-opacity-25">
            <h3 class="mb-2">Komponenty logistyki <span class="text-muted small fw-normal">(<code>x-logistics.*</code>)</span></h3>
            <p class="text-muted small mb-3">
                Używane w <code>/departures/create-v2</code>, zjeździe i transferze. Nagłówek sekcji: <code>x-logistics.section-header</code>; ramka kroku trasy: <code>x-logistics.route-planning-frame</code>; wiersz dat + transport: partial <code>trip-logistics-header</code> (tylko w kontekście Livewire — podgląd pełnego wiersza tylko w planerze).
            </p>

            {{-- Ściąga: klasy CSS nagłówka planera --}}
            <div class="rounded-3 p-3 mb-4" style="background: rgba(0,0,0,0.22); border: 1px solid rgba(255,255,255,0.08);">
                <h5 class="mb-2 small fw-semibold text-uppercase text-muted" style="letter-spacing: .04em;">Ściąga — klasy nagłówka planera</h5>
                <p class="text-muted small mb-2 mb-md-3">Zdefiniowane w <code>resources/css/app.css</code> (prefiks <code>.logistics-trip-header-*</code>):</p>
                <ul class="small text-muted mb-3 ps-3" style="line-height: 1.65;">
                    <li><code>.logistics-trip-header-row</code> — wiersz Bootstrap (daty | Czym | szczegóły)</li>
                    <li><code>.logistics-trip-header-card</code> — karta kolumny; <code>min-height: 106px</code></li>
                    <li><code>.logistics-trip-header-card--invalid</code> — mocna obwódka błędu (daty / brak trybu / lotniska niekompletne)</li>
                    <li><code>.logistics-trip-header-card--invalid-soft</code> — krok „Typ punktu” (lotnisko / dworzec)</li>
                    <li><code>.logistics-trip-header-control</code> — input / select / przyciski w nagłówku</li>
                    <li><code>.logistics-trip-header-control-row</code> — rząd przycisków (np. Czym, Lotnisko/Dworzec)</li>
                    <li><code>.logistics-trip-header-hint</code> — podpowiedzi i komunikaty (<code>font-size: 0.72rem</code>)</li>
                </ul>
                <p class="text-muted small mb-2">Partial hubów (Start/Cel, bez Livewire nie renderuj): <code>resources/views/components/logistics/partials/trip-header-hub-select.blade.php</code></p>
                <div class="row g-2 mb-0">
                    <div class="col-md-4">
                        <div class="rounded-2 p-2 small text-muted logistics-trip-header-card" style="border: 1px solid rgba(255,255,255,0.12);">Karta bazowa <code>.logistics-trip-header-card</code></div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-2 p-2 small text-muted logistics-trip-header-card logistics-trip-header-card--invalid">Stan błędu <code>--invalid</code></div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-2 p-2 small text-muted logistics-trip-header-card logistics-trip-header-card--invalid-soft">Krok typu punktu <code>--invalid-soft</code></div>
                    </div>
                </div>
            </div>

            @php
                $demoVehicle = (object) ['capacity' => 5, 'brand' => 'Volkswagen', 'model' => 'Transporter'];
                $demoSeats = [
                    0 => ['employee_id' => null, 'position' => 'driver', 'external_driver' => true],
                    1 => ['employee_id' => 101, 'position' => 'passenger', 'external_driver' => false],
                    2 => ['employee_id' => 102, 'position' => 'passenger', 'external_driver' => false],
                    3 => ['employee_id' => null, 'position' => 'passenger', 'external_driver' => false],
                    4 => ['employee_id' => null, 'position' => 'passenger', 'external_driver' => false],
                ];
                $demoEmployees = collect([
                    (object) ['id' => 101, 'first_name' => 'Anna', 'last_name' => 'Kowalska', 'full_name' => 'Anna Kowalska', 'image_url' => null],
                    (object) ['id' => 102, 'first_name' => 'Jan', 'last_name' => 'Nowak', 'full_name' => 'Jan Nowak', 'image_url' => null],
                ]);
            @endphp

            <div class="mb-3">
                <x-logistics.section-header title="Przykład nagłówka sekcji (jak wyjazd / zjazd)" />
                <x-logistics.route-planning-frame title="Przykład ramy planowania trasy (slot)" class="mb-3">
                    <p class="text-muted small mb-0">Tu trafi zawartość komponentu trasy (np. <code>step4-route-planning</code> w planerze wyjazdu) lub odpowiednik w transferze — jedna spójna obwódka.</p>
                </x-logistics.route-planning-frame>
            </div>

            <div class="rounded-3 p-3 mb-3" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);">
                <h5 class="mb-3 small fw-semibold text-muted">Ściąga — <code>x-logistics.transport-mode-toggle</code></h5>
                <p class="text-muted small mb-3">Props: <code>mode</code> (<code>null</code>|<code>public</code>|<code>own</code>), <code>hub-kind</code> (<code>airport</code>|<code>station</code>|null), <code>interactive</code>, <code>required</code>. Etykiety przycisku publicznego w PHP: <code>TransportModeToggle</code>.</p>
                <div class="row g-3 align-items-stretch mb-3">
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="text-muted small mb-1">Brak wyboru (wymagane)</div>
                        <x-logistics.transport-mode-toggle :mode="null" :interactive="false" />
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="text-muted small mb-1">Publiczny + własny (nieaktywne)</div>
                        <x-logistics.transport-mode-toggle mode="public" :interactive="false" />
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="text-muted small mb-1">Własny</div>
                        <x-logistics.transport-mode-toggle mode="own" :interactive="false" />
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="text-muted small mb-1">Publiczny — hub lotnisko → „Samolot”</div>
                        <x-logistics.transport-mode-toggle mode="public" hub-kind="airport" :interactive="false" />
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="text-muted small mb-1">Publiczny — hub dworzec → „Bus / pociąg”</div>
                        <x-logistics.transport-mode-toggle mode="public" hub-kind="station" :interactive="false" />
                    </div>
                </div>
                <x-logistics.vehicle-seat-grid
                    :vehicle="$demoVehicle"
                    :vehicle-seats="$demoSeats"
                    :selected-employees="$demoEmployees"
                    wire-key-prefix="catalog-lvs"
                    :interactive="false"
                />
            </div>
            <pre class="small text-muted mb-2 p-3 rounded-3" style="background: rgba(0,0,0,0.25); font-size: 0.72rem; overflow-x: auto;"><code>&lt;x-logistics.vehicle-seat-grid
    :vehicle="$selectedVehicle"
    :vehicle-seats="$vehicleSeats"
    :selected-employees="$this->selectedEmployees"
    wire-key-prefix="vs"
/&gt;</code></pre>
            <pre class="small text-muted mb-2 p-3 rounded-3" style="background: rgba(0,0,0,0.25); font-size: 0.72rem; overflow-x: auto;"><code>&lt;x-logistics.transport-mode-toggle
    :mode="$transportMode"
    :hub-kind="$publicTransportHubKind"
/&gt;</code></pre>
            <pre class="small text-muted mb-0 p-3 rounded-3" style="background: rgba(0,0,0,0.25); font-size: 0.72rem; overflow-x: auto;"><code>@@include('components.logistics.trip-logistics-header', [
    'tripLogisticsHeader' => [
        'title' => 'Szczegóły wyjazdu',     // albo zjazdu
        'firstWire' => 'departureDate',    // albo returnDate
        'firstLabel' => 'Data wyjazdu',
        'datesHelp' => '…',
        'vehiclePoolHint' => 'departure',  // albo 'return'
    ],
])
// W widoku Livewire: $endDate, $transportMode, $publicTransportHubKind, …</code></pre>
        </div>

        {{-- Planer wyjazdu: krok 1 — przypisania (klasy .s1-*) --}}
        <div class="mb-5 pb-4 border-bottom border-secondary border-opacity-25">
            <h3 class="mb-2">Planer wyjazdu — krok 1 <span class="text-muted small fw-normal">(<code>x-departure.planner-step1-assignments-styles</code>)</span></h3>
            <p class="text-muted small mb-3">
                Arkusz CSS dla <code>resources/views/livewire/steps/step1-project-assignments.blade.php</code> (prefiks <code>.s1-*</code>: karty pracowników, bloki projektów, strefy drop, chipy). Podgląd statyczny — w planerze ten sam markup jest podpięty pod Livewire (drag &amp; drop).
            </p>

            <x-departure.planner-step1-assignments-styles />

            <div class="rounded-3 p-3 mb-3" style="background: rgba(0,0,0,0.22); border: 1px solid rgba(255,255,255,0.08);">
                <h5 class="mb-2 small fw-semibold text-uppercase text-muted" style="letter-spacing: .04em;">Ściąga — klasy <code>.s1-*</code></h5>
                <ul class="small text-muted mb-0 ps-3" style="line-height: 1.65;">
                    <li><code>.s1-panel</code>, <code>.s1-filters</code>, <code>.s1-hint</code> — layout lewej kolumny</li>
                    <li><code>.s1-emp-card</code>, <code>.s1-emp-avatar</code>, <code>.s1-emp-role-pill</code> — karta pracownika (lista)</li>
                    <li><code>.s1-project-block</code>, <code>.s1-roles-grid</code>, <code>.s1-role-card</code>, <code>.s1-gap-pill</code> — projekt i role</li>
                    <li><code>.s1-drop-zone</code>, <code>.s1-assigned-chip</code> — przeciąganie i przypisania</li>
                    <li><code>.s1-full-banner</code>, <code>.s1-pagination-*</code>, <code>.s1-empty-state</code> — komunikaty i paginacja</li>
                </ul>
            </div>

            <div class="row g-3 align-items-stretch">
                <div class="col-lg-5">
                    <x-ui.card class="s1-panel">
                        <div class="fw-semibold s1-panel-title mb-2">Dostępni pracownicy</div>
                        <div class="s1-filters">
                            <input type="text" class="form-control form-control-sm" placeholder="Szukaj pracownika…" readonly tabindex="-1" aria-hidden="true">
                        </div>
                        <div class="s1-hint">
                            <i class="bi bi-grip-horizontal"></i>
                            Przeciągnij pracownika na rolę po prawej
                        </div>
                        <div class="s1-emp-card s1-emp-card--static">
                            <div class="s1-emp-avatar">AK</div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="s1-emp-name text-truncate">Anna Kowalska</div>
                                <div class="s1-emp-roles">
                                    <span class="s1-emp-role-pill">Monter</span>
                                    <span class="s1-emp-role-pill">BHP</span>
                                </div>
                            </div>
                            <i class="bi bi-grip-vertical text-muted s1-grip-icon"></i>
                        </div>
                        <div class="s1-full-banner mb-0">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            Przykład: brak miejsca w aucie (banner)
                        </div>
                    </x-ui.card>
                </div>
                <div class="col-lg-7">
                    <x-ui.card class="s1-panel">
                        <div class="fw-semibold s1-panel-title mb-1">Jakich ludzi brakuje po przyjeździe?</div>
                        <div class="s1-section-subtitle mb-3">Braki w rolach — demo układu (bez danych z API).</div>
                        <div class="s1-project-block mb-0">
                            <div class="s1-project-header">
                                <div class="s1-project-name">Projekt demonstracyjny</div>
                                <div class="s1-project-loc">
                                    <i class="bi bi-geo-alt-fill s1-project-loc-icon"></i>
                                    Warszawa
                                </div>
                            </div>
                            <div class="s1-roles-grid">
                                <div class="s1-role-card">
                                    <div class="s1-role-header">
                                        <span class="s1-role-name">Elektryk</span>
                                        <span class="s1-gap-pill">
                                            <i class="bi bi-person-dash"></i>
                                            1 brak.
                                        </span>
                                    </div>
                                    <div class="s1-drop-zone s1-drop-zone--static">
                                        <i class="bi bi-person-plus s1-drop-zone-icon"></i>
                                        <span class="s1-drop-hint">Przeciągnij pracownika na tę rolę</span>
                                    </div>
                                    <div class="s1-assigned-list">
                                        <div class="s1-assigned-chip">
                                            <div class="s1-chip-avatar">JN</div>
                                            <span class="s1-chip-name">Jan Nowak</span>
                                            <span class="s1-chip-remove opacity-50" title="(demo)"><i class="bi bi-x-lg"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </div>

            <pre class="small text-muted mb-0 mt-3 p-3 rounded-3" style="background: rgba(0,0,0,0.25); font-size: 0.72rem; overflow-x: auto;"><code>&lt;x-departure.planner-step1-assignments-styles /&gt;</code></pre>
        </div>

        {{-- Hero Card Example --}}
        <div class="mb-5">
            <h3 class="mb-4">Komponent x-ui.hero-card</h3>
            <x-ui.hero-card
                title="Are you ready"
                subtitle="for an adventure?"
                icon="rocket-takeoff"
                iconColor="primary"
                variant="gradient"
                imagePosition="right"
            >
                <x-slot name="image">
                    <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(168, 85, 247, 0.2)); border-radius: 16px; padding: 2rem; min-height: 300px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-play-circle" style="font-size: 5rem; color: var(--primary);"></i>
                    </div>
                </x-slot>
                <p class="mb-0">
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Minima officia consequatur adipisci tenetur repudiandae rerum quos.
                </p>
            </x-ui.hero-card>
        </div>
        <hr class="mb-5">

        <h3 class="mb-4">Komponenty x-ui.*</h3>

        {{-- x-ui.button --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.button</h4>
            <p class="text-muted small">Varianty: primary, ghost, danger, warning, success | Action: create, edit, save, delete, back, view</p>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <x-ui.button variant="primary" action="create">Dodaj</x-ui.button>
                <x-ui.button variant="ghost" action="edit">Edytuj</x-ui.button>
                <x-ui.button variant="danger" action="delete">Usuń</x-ui.button>
                <x-ui.button variant="success" action="save">Zapisz</x-ui.button>
                <x-ui.button variant="ghost" action="back">Powrót</x-ui.button>
                <x-ui.button variant="ghost" action="view">Zobacz</x-ui.button>
                <x-ui.button variant="primary" href="{{ route('home') }}">Link</x-ui.button>
            </div>
        </div>
        <hr>

        {{-- x-ui.badge --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.badge</h4>
            <p class="text-muted small">Varianty: success, danger, warning, info, accent</p>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <x-ui.badge variant="success">Sukces</x-ui.badge>
                <x-ui.badge variant="danger">Błąd</x-ui.badge>
                <x-ui.badge variant="warning">Ostrzeżenie</x-ui.badge>
                <x-ui.badge variant="info">Info</x-ui.badge>
                <x-ui.badge variant="accent">Akcent</x-ui.badge>
            </div>
        </div>
        <hr>

        {{-- x-ui.clickable-badge --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.clickable-badge</h4>
            <p class="text-muted small">Varianty: success, danger, warning, info, accent | Props: href, route, routeParams | Hover effect podobny do przycisku</p>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <x-ui.clickable-badge variant="success" href="{{ route('home') }}">Sukces (link)</x-ui.clickable-badge>
                <x-ui.clickable-badge variant="danger" href="{{ route('home') }}">Błąd (link)</x-ui.clickable-badge>
                <x-ui.clickable-badge variant="warning" href="{{ route('home') }}">Ostrzeżenie (link)</x-ui.clickable-badge>
                <x-ui.clickable-badge variant="info" route="home">Info (route)</x-ui.clickable-badge>
                <x-ui.clickable-badge variant="accent" href="{{ route('home') }}">Akcent (link)</x-ui.clickable-badge>
            </div>
            <p class="text-muted small mb-0">Najedź myszką na badge'y powyżej, aby zobaczyć efekt hover (podobny do przycisków).</p>
        </div>
        <hr>

        {{-- x-ui.card --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.card</h4>
            <p class="text-muted small">Varianty: default, hover, elevated | Props: label</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <x-ui.card label="Karta z etykietą">
                        <p class="mb-0">To jest zawartość karty z etykietą.</p>
                    </x-ui.card>
                </div>
                <div class="col-md-4 mb-3">
                    <x-ui.card variant="hover">
                        <p class="mb-0">Karta z efektem hover.</p>
                    </x-ui.card>
                </div>
                <div class="col-md-4 mb-3">
                    <x-ui.card variant="elevated">
                        <p class="mb-0">Karta z podniesionym efektem.</p>
                    </x-ui.card>
                </div>
            </div>
        </div>
        <hr>

        {{-- x-ui.page-header --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.page-header</h4>
            <p class="text-muted small">Sloty: left (lewy slot), right (prawy slot)</p>
            <x-ui.card>
                <x-ui.page-header title="Przykładowy nagłówek">
                    <x-slot name="left">
                        <x-ui.button variant="ghost" action="back">Powrót</x-ui.button>
                    </x-slot>
                    <x-slot name="right">
                        <x-ui.button variant="primary" action="create">Dodaj</x-ui.button>
                    </x-slot>
                </x-ui.page-header>
            </x-ui.card>
        </div>
        <hr>

        {{-- x-ui.input --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.input</h4>
            <p class="text-muted small">Type: text, textarea, select, checkbox, date, email, password, number, file</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <x-ui.input type="text" name="test_text" label="Tekst" placeholder="Wpisz tekst" />
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.input type="email" name="test_email" label="Email" placeholder="email@example.com" />
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.input type="date" name="test_date" label="Data" />
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.input type="number" name="test_number" label="Liczba" />
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.input type="textarea" name="test_textarea" label="Textarea" rows="3" />
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.input type="select" name="test_select" label="Select">
                        <option value="">Wybierz...</option>
                        <option value="1">Opcja 1</option>
                        <option value="2">Opcja 2</option>
                    </x-ui.input>
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.input type="checkbox" name="test_checkbox" label="Checkbox" />
                </div>
            </div>
        </div>
        <hr>

        {{-- x-ui.empty-state --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.empty-state</h4>
            <p class="text-muted small">Props: icon, message, inTable, colspan | Slot dla przycisku</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <x-ui.empty-state icon="inbox" message="Brak danych">
                            <x-ui.button variant="primary" action="create">Dodaj pierwszy element</x-ui.button>
                        </x-ui.empty-state>
                    </x-ui.card>
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <x-ui.empty-state icon="car-front" message="Brak pojazdów" />
                    </x-ui.card>
                </div>
            </div>
        </div>
        <hr>

        {{-- x-ui.alert --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.alert</h4>
            <p class="text-muted small">Varianty: info, success, danger, warning | Props: icon, title, dismissible</p>
            <div class="mb-3">
                <x-ui.alert variant="success" title="Sukces!" dismissible>
                    Operacja zakończona pomyślnie.
                </x-ui.alert>
            </div>
            <div class="mb-3">
                <x-ui.alert variant="danger" title="Błąd!" dismissible>
                    Wystąpił błąd podczas operacji.
                </x-ui.alert>
            </div>
            <div class="mb-3">
                <x-ui.alert variant="warning" title="Ostrzeżenie!" dismissible>
                    Uwaga: Sprawdź wprowadzone dane.
                </x-ui.alert>
            </div>
            <div class="mb-3">
                <x-ui.alert variant="info" title="Informacja" dismissible>
                    To jest komunikat informacyjny.
                </x-ui.alert>
            </div>
        </div>
        <hr>

        {{-- x-ui.table-header --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.table-header</h4>
            <p class="text-muted small">Slot: actions</p>
            <x-ui.card>
                <x-ui.table-header title="Tytuł tabeli" subtitle="Podtytuł">
                    <x-slot name="actions">
                        <x-ui.button variant="ghost" class="btn-sm">Akcja</x-ui.button>
                    </x-slot>
                </x-ui.table-header>
            </x-ui.card>
        </div>
        <hr>

        {{-- x-ui.avatar --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.avatar</h4>
            <p class="text-muted small">Props: imageUrl, initials, size, shape (circle, square, rounded), border</p>
            <div class="d-flex gap-3 align-items-center mb-3">
                <x-ui.avatar initials="JD" size="50px" />
                <x-ui.avatar initials="AB" size="60px" shape="square" />
                <x-ui.avatar initials="CD" size="70px" shape="rounded" />
            </div>
        </div>
        <hr>

        {{-- x-ui.person --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.person</h4>
            <p class="text-muted small">Kompaktowy element z avatarem, imieniem i emailem. Props: user, showEmail, avatarSize, avatarShape, link, nameClass, emailClass</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <h6 class="mb-3">Bez linku (domyślnie)</h6>
                        @if(auth()->check())
                            <x-ui.person :user="auth()->user()" />
                        @else
                            <x-ui.person :user="(object)['name' => 'Jan Kowalski', 'email' => 'jan.kowalski@example.com']" />
                        @endif
                    </x-ui.card>
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <h6 class="mb-3">Z linkiem</h6>
                        @if(auth()->check())
                            <x-ui.person :user="auth()->user()" :link="true" />
                        @else
                            <x-ui.person :user="(object)['id' => 1, 'name' => 'Anna Nowak', 'email' => 'anna.nowak@example.com']" :link="true" />
                        @endif
                    </x-ui.card>
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <h6 class="mb-3">Bez emaila</h6>
                        @if(auth()->check())
                            <x-ui.person :user="auth()->user()" :show-email="false" />
                        @else
                            <x-ui.person :user="(object)['name' => 'Piotr Wiśniewski']" :show-email="false" />
                        @endif
                    </x-ui.card>
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <h6 class="mb-3">Mniejszy avatar (32px)</h6>
                        @if(auth()->check())
                            <x-ui.person :user="auth()->user()" avatar-size="32px" />
                        @else
                            <x-ui.person :user="(object)['name' => 'Maria Zielińska', 'email' => 'maria.zielinska@example.com']" avatar-size="32px" />
                        @endif
                    </x-ui.card>
                </div>
            </div>
            <div class="mb-3">
                <x-ui.card>
                    <h6 class="mb-3">W tabeli (przykład użycia)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Użytkownik</th>
                                    <th>Rola</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        @if(auth()->check())
                                            <x-ui.person :user="auth()->user()" avatar-size="32px" />
                                        @else
                                            <x-ui.person :user="(object)['name' => 'Jan Kowalski', 'email' => 'jan.kowalski@example.com']" avatar-size="32px" />
                                        @endif
                                    </td>
                                    <td><x-ui.badge variant="info">Admin</x-ui.badge></td>
                                    <td><x-ui.badge variant="success">Aktywny</x-ui.badge></td>
                                </tr>
                                <tr>
                                    <td>
                                        <x-ui.person :user="(object)['name' => 'Anna Nowak', 'email' => 'anna.nowak@example.com']" avatar-size="32px" />
                                    </td>
                                    <td><x-ui.badge variant="accent">Manager</x-ui.badge></td>
                                    <td><x-ui.badge variant="success">Aktywny</x-ui.badge></td>
                                </tr>
                                <tr>
                                    <td>
                                        <x-ui.person :user="(object)['name' => 'Piotr Wiśniewski', 'email' => 'piotr.wisniewski@example.com']" avatar-size="32px" />
                                    </td>
                                    <td><x-ui.badge variant="warning">User</x-ui.badge></td>
                                    <td><x-ui.badge variant="danger">Nieaktywny</x-ui.badge></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>
        </div>
        <hr>

        {{-- x-ui.progress --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.progress</h4>
            <p class="text-muted small">Props: value, max, showLabel, variant (default, success, danger, warning)</p>
            <div class="mb-3">
                <x-ui.progress value="25" max="100" showLabel />
            </div>
            <div class="mb-3">
                <x-ui.progress value="50" max="100" variant="success" showLabel />
            </div>
            <div class="mb-3">
                <x-ui.progress value="75" max="100" variant="warning" showLabel />
            </div>
            <div class="mb-3">
                <x-ui.progress value="90" max="100" variant="danger" showLabel />
            </div>
        </div>
        <hr>

        {{-- x-ui.detail-item --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.detail-item</h4>
            <p class="text-muted small">Props: label, fullWidth</p>
            <x-ui.card>
                <x-ui.detail-list>
                    <x-ui.detail-item label="Nazwa">Wartość 1</x-ui.detail-item>
                    <x-ui.detail-item label="Opis">Wartość 2</x-ui.detail-item>
                    <x-ui.detail-item label="Pełna szerokość" fullWidth>Wartość 3</x-ui.detail-item>
                </x-ui.detail-list>
            </x-ui.card>
        </div>
        <hr>

        {{-- x-ui.detail-list --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.detail-list</h4>
            <p class="text-muted small">Kontener dla x-ui.detail-item</p>
            <x-ui.card>
                <x-ui.detail-list>
                    <x-ui.detail-item label="Pole 1">Wartość 1</x-ui.detail-item>
                    <x-ui.detail-item label="Pole 2">Wartość 2</x-ui.detail-item>
                </x-ui.detail-list>
            </x-ui.card>
        </div>
        <hr>

        {{-- x-ui.image-preview --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.image-preview</h4>
            <p class="text-muted small">Props: inputId, previewId, imgId, currentImage, currentImageUrl, showCurrentImage</p>
            <x-ui.card>
                <x-ui.image-preview />
            </x-ui.card>
        </div>
        <hr>

        {{-- x-ui.errors --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.errors</h4>
            <p class="text-muted small">Wyświetla błędy walidacji</p>
            <x-ui.card>
                <p class="text-muted small">Komponent automatycznie wyświetla błędy z sesji.</p>
            </x-ui.card>
        </div>
        <hr>

        {{-- x-tooltip --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-tooltip</h4>
            <p class="text-muted small">Komponent tooltip z żaróweczką - wyświetla wskazówkę na hover. Props: title (domyślnie "Wskazówka"), text (opcjonalny tekst przed żaróweczką)</p>
            <x-ui.card>
                <p class="mb-3">
                    To jest zwykły tekst, a 
                    <x-tooltip title="Wskazówka" text="tutaj">
                        Żółta żaróweczka = hint. Tooltip wyskakuje tylko na ten fragment tekstu.
                    </x-tooltip>
                    i reszta zdania bez żadnych efektów.
                </p>
                <p class="mb-3">
                    Przykład użycia w badge: 
                    <span class="badge bg-primary">
                        Status 
                        <x-tooltip title="Informacja">
                            Ten badge pokazuje aktualny status przypisania pracownika do projektu.
                        </x-tooltip>
                    </span>
                </p>
                <p class="mb-0">
                    Przykład tylko z żaróweczką (bez tekstu przed): 
                    <x-tooltip title="Szczegóły">
                        To jest przykład użycia komponentu bez tekstu przed żaróweczką - tylko ikona.
                    </x-tooltip>
                </p>
            </x-ui.card>
        </div>
        <hr>

        {{-- x-ui.delete-form --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.delete-form</h4>
            <p class="text-muted small">Props: url, message, buttonClass, buttonVariant, buttonText</p>
            <x-ui.delete-form url="#" message="Czy na pewno chcesz usunąć?" />
        </div>
        <hr>

        {{-- x-ui.navbar --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.navbar</h4>
            <p class="text-muted small">Props: brand, brandUrl | Slot dla elementów nawigacji</p>
            <x-ui.card>
                <x-ui.navbar brand="Stocznia PRO" brandUrl="#">
                    <x-ui.button variant="ghost" class="text-white">Menu 1</x-ui.button>
                    <x-ui.button variant="ghost" class="text-white">Menu 2</x-ui.button>
                </x-ui.navbar>
            </x-ui.card>
        </div>
        <hr>

        <h3 class="mb-4 mt-5">Komponenty luzem</h3>

        {{-- action-buttons --}}
        <div class="mb-4">
            <h4 class="fw-semibold">action-buttons</h4>
            <p class="text-muted small">Props: viewRoute, editRoute, deleteRoute, deleteMessage, size, resource</p>
            <x-action-buttons
                viewRoute="#"
                editRoute="#"
                deleteRoute="#"
                deleteMessage="Czy na pewno chcesz usunąć?"
            />
        </div>
        <hr>

        {{-- x-ui.action-buttons --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.action-buttons</h4>
            <p class="text-muted small">Props: viewRoute, editRoute, deleteRoute, deleteMessage, size, gap, class | Slot dla custom przycisków</p>
            <div class="mb-3">
                <x-ui.action-buttons
                    viewRoute="#"
                    editRoute="#"
                    deleteRoute="#"
                    deleteMessage="Czy na pewno chcesz usunąć?"
                />
            </div>
            <div class="mb-3">
                <x-ui.action-buttons>
                    <x-ui.button variant="primary" class="btn-sm">Custom 1</x-ui.button>
                    <x-ui.button variant="ghost" class="btn-sm">Custom 2</x-ui.button>
                </x-ui.action-buttons>
            </div>
        </div>
        <hr>

        <h3 class="mb-4 mt-5">Klasy Bootstrap przepisane w app.css</h3>

        {{-- Karty --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.card, .card-body, .card-header</h4>
            <p class="text-muted small">Glassmorphism z backdrop-filter</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Nagłówek karty</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">To jest zawartość karty z przepisanymi stylami.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-0">Karta bez nagłówka.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>

        {{-- Przyciski --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.btn, .btn-primary, .btn-outline-secondary, .btn-danger, .btn-warning, .btn-success</h4>
            <p class="text-muted small">Przepisane z gradientami i efektami hover</p>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <button class="btn btn-primary">Primary</button>
                <button class="btn btn-outline-secondary">Ghost</button>
                <button class="btn btn-danger">Danger</button>
                <button class="btn btn-warning">Warning</button>
                <button class="btn btn-success">Success</button>
            </div>
        </div>
        <hr>

        {{-- Formularze --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.form-control, .form-select, .form-label</h4>
            <p class="text-muted small">Przepisane z dark theme</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Input</label>
                    <input type="text" class="form-control" placeholder="Wpisz tekst">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select</label>
                    <select class="form-select">
                        <option>Opcja 1</option>
                        <option>Opcja 2</option>
                    </select>
                </div>
            </div>
        </div>
        <hr>

        {{-- Badge --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.badge, .badge-success, .badge-danger, .badge-info, .badge-warning, .badge-accent</h4>
            <p class="text-muted small">Przepisane z przezroczystym tłem</p>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <span class="badge badge-success">Success</span>
                <span class="badge badge-danger">Danger</span>
                <span class="badge badge-info">Info</span>
                <span class="badge badge-warning">Warning</span>
                <span class="badge badge-accent">Accent</span>
            </div>
        </div>
        <hr>

        {{-- Tabela --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.table, .table thead th, .table tbody tr, .table td</h4>
            <p class="text-muted small">Przepisane z odstępami między wierszami</p>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kolumna 1</th>
                            <th>Kolumna 2</th>
                            <th>Kolumna 3</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Wiersz 1</td>
                            <td>Dane</td>
                            <td>Info</td>
                        </tr>
                        <tr>
                            <td>Wiersz 2</td>
                            <td>Dane</td>
                            <td>Info</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <hr>

        {{-- Alerty --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.alert, .alert-success, .alert-danger, .alert-warning, .alert-info</h4>
            <p class="text-muted small">Przepisane z border-left</p>
            <div class="mb-3">
                <div class="alert alert-success">Sukces!</div>
            </div>
            <div class="mb-3">
                <div class="alert alert-danger">Błąd!</div>
            </div>
            <div class="mb-3">
                <div class="alert alert-warning">Ostrzeżenie!</div>
            </div>
            <div class="mb-3">
                <div class="alert alert-info">Informacja</div>
            </div>
        </div>
        <hr>

        {{-- Progress --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.progress-ui, .progress-bar-ui</h4>
            <p class="text-muted small">Własne klasy dla progress bar</p>
            <div class="progress-ui mb-3">
                <div class="progress-bar-ui" style="width: 50%;"></div>
            </div>
        </div>
        <hr>

        {{-- Utility classes --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Klasy utility: .text-primary, .text-accent, .text-muted, .text-success, .text-warning, .text-danger</h4>
            <p class="text-muted small">Przepisane kolory tekstu</p>
            <div class="d-flex gap-3 flex-wrap mb-3">
                <span class="text-primary">Primary</span>
                <span class="text-accent">Accent</span>
                <span class="text-muted">Muted</span>
                <span class="text-success">Success</span>
                <span class="text-warning">Warning</span>
                <span class="text-danger">Danger</span>
            </div>
        </div>
        <hr>

        {{-- Background classes --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Klasy tła: .bg-white, .bg-light, .bg-body</h4>
            <p class="text-muted small">Przepisane na dark theme</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="bg-white p-3 rounded">.bg-white</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="bg-light p-3 rounded">.bg-light</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="bg-body p-3 rounded border">.bg-body</div>
                </div>
            </div>
        </div>
        <hr>

        {{-- Border classes --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Klasy border: .border, .border-top, .border-bottom, .border-start, .border-end</h4>
            <p class="text-muted small">Przepisane z glass-border</p>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="p-3 border">.border</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border-top">.border-top</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border-bottom">.border-bottom</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border-start">.border-start</div>
                </div>
            </div>
        </div>
        <hr>

        {{-- Avatar --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.avatar-ui</h4>
            <p class="text-muted small">Własna klasa dla avatarów</p>
            <div class="d-flex gap-3 align-items-center mb-3">
                <div class="avatar-ui">JD</div>
                <div class="avatar-ui">AB</div>
                <div class="avatar-ui">CD</div>
            </div>
        </div>
        <hr>

        {{-- Paginacja --}}
        <div class="mb-4">
            <h4 class="fw-semibold">.pagination, .page-link</h4>
            <p class="text-muted small">Przepisana paginacja z info o rekordach</p>
            <nav class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="small text-muted mb-0">
                        Pokazano <span class="fw-semibold">1</span> do <span class="fw-semibold">10</span> z <span class="fw-semibold">25</span> wyników
                    </p>
                </div>
                <div>
                    <ul class="pagination mb-0">
                        <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i></span></li>
                        <li class="page-item active"><span class="page-link">1</span></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i></a></li>
                    </ul>
                </div>
            </nav>
        </div>
        <hr>

        <h3 class="mb-4 mt-5">Layout - Podział kontenera na kolumny</h3>

        {{-- 2 kolumny --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Podział na 2 kolumny (col-md-6)</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <h5>Lewa kolumna</h5>
                        <p class="mb-0">Zawartość lewej kolumny</p>
                    </x-ui.card>
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <h5>Prawa kolumna</h5>
                        <p class="mb-0">Zawartość prawej kolumny</p>
                    </x-ui.card>
                </div>
            </div>
        </div>
        <hr>

        {{-- 3 kolumny --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Podział na 3 kolumny (col-md-4)</h4>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <x-ui.card>
                        <h5>Kolumna 1</h5>
                        <p class="mb-0">Zawartość kolumny 1</p>
                    </x-ui.card>
                </div>
                <div class="col-md-4 mb-3">
                    <x-ui.card>
                        <h5>Kolumna 2</h5>
                        <p class="mb-0">Zawartość kolumny 2</p>
                    </x-ui.card>
                </div>
                <div class="col-md-4 mb-3">
                    <x-ui.card>
                        <h5>Kolumna 3</h5>
                        <p class="mb-0">Zawartość kolumny 3</p>
                    </x-ui.card>
                </div>
            </div>
        </div>
        <hr>

        {{-- Nierówny podział --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Nierówny podział (col-md-8 + col-md-4)</h4>
            <div class="row">
                <div class="col-md-8 mb-3">
                    <x-ui.card>
                        <h5>Główna kolumna (8/12)</h5>
                        <p class="mb-0">Szersza kolumna główna</p>
                    </x-ui.card>
                </div>
                <div class="col-md-4 mb-3">
                    <x-ui.card>
                        <h5>Boczna kolumna (4/12)</h5>
                        <p class="mb-0">Węższa kolumna boczna</p>
                    </x-ui.card>
                </div>
            </div>
        </div>
        <hr>

        {{-- Zagnieżdżanie --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Zagnieżdżanie (zazębianie) - Przykład 1</h4>
            <p class="text-muted small">Karta z wewnętrznym podziałem na kolumny</p>
            <x-ui.card>
                <h5 class="mb-3">Główna karta</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <x-ui.card variant="hover">
                            <h6>Zagnieżdżona karta 1</h6>
                            <p class="mb-0 small">Karta wewnątrz karty</p>
                        </x-ui.card>
                    </div>
                    <div class="col-md-6 mb-3">
                        <x-ui.card variant="hover">
                            <h6>Zagnieżdżona karta 2</h6>
                            <p class="mb-0 small">Karta wewnątrz karty</p>
                        </x-ui.card>
                    </div>
                </div>
            </x-ui.card>
        </div>
        <hr>

        {{-- Zagnieżdżanie 2 --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Zagnieżdżanie (zazębianie) - Przykład 2</h4>
            <p class="text-muted small">Komponenty wewnątrz kart z podziałem</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <h5 class="mb-3">Karta z komponentami</h5>
                        <div class="mb-3">
                            <x-ui.input type="text" name="nested1" label="Input w karcie" />
                        </div>
                        <div class="mb-3">
                            <x-ui.badge variant="info">Badge w karcie</x-ui.badge>
                        </div>
                        <x-ui.button variant="primary" class="btn-sm">Przycisk w karcie</x-ui.button>
                    </x-ui.card>
                </div>
                <div class="col-md-6 mb-3">
                    <x-ui.card>
                        <h5 class="mb-3">Karta z tabelą</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nazwa</th>
                                        <th>Wartość</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Pole 1</td>
                                        <td>Wartość 1</td>
                                    </tr>
                                    <tr>
                                        <td>Pole 2</td>
                                        <td>Wartość 2</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </div>
        <hr>

        {{-- Zagnieżdżanie 3 --}}
        <div class="mb-4">
            <h4 class="fw-semibold">Zagnieżdżanie (zazębianie) - Przykład 3</h4>
            <p class="text-muted small">Kompleksowy layout z wieloma poziomami</p>
            <x-ui.card>
                <h5 class="mb-4">Główna sekcja</h5>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <x-ui.card variant="hover">
                            <h6 class="mb-3">Sekcja główna</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <x-ui.input type="text" name="nested2" label="Pole 1" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <x-ui.input type="text" name="nested3" label="Pole 2" />
                                </div>
                            </div>
                            <x-ui.button variant="primary" class="btn-sm">Akcja</x-ui.button>
                        </x-ui.card>
                    </div>
                    <div class="col-md-4 mb-3">
                        <x-ui.card variant="hover">
                            <h6 class="mb-3">Panel boczny</h6>
                            <div class="mb-3">
                                <x-ui.badge variant="success">Status</x-ui.badge>
                            </div>
                            <div class="mb-3">
                                <x-ui.progress value="75" max="100" showLabel />
                            </div>
                            <x-ui.empty-state icon="info-circle" message="Brak dodatkowych danych" />
                        </x-ui.card>
                    </div>
                </div>
            </x-ui.card>
        </div>
        <hr>

    </div>

    <div class="ui-tooltip">
        <i class="bi bi-info-circle ui-tooltip-icon"></i>
    
        <span style="position:relative; cursor:help; text-decoration:underline dotted; text-underline-offset:3px;">
            Najedź tutaj
            <span style="
                position:absolute;
                top:130%;
                left:50%;
                transform:translateX(-50%);
                min-width:220px;
                background:rgba(20,20,20,0.9);
                color:#fff;
                padding:10px 14px;
                border-radius:10px;
                box-shadow:0 10px 30px rgba(0,0,0,.35);
                font-size:13px;
                line-height:1.4;
                opacity:0;
                pointer-events:none;
                transition:opacity .15s ease, transform .15s ease;
                z-index:999;
            ">
                <span style="display:flex; gap:8px; align-items:flex-start;">
                    <i class="bi bi-info-circle" style="color:#3b82f6; margin-top:2px;"></i>
                    <span>
                        To jest wskazówka wyświetlana po najechaniu na tekst.
                    </span>
                </span>
            </span>
        </span>
        
        <style>
        span:hover > span {
            opacity:1;
            transform:translateX(-50%) translateY(4px);
        }
        </style>

        {{-- x-ui.cal --}}
        <div class="mb-4">
            <h4 class="fw-semibold">x-ui.cal</h4>
            <p class="text-muted small">Komponent kalendarza miesięcznego w ciemnym stylu. Props: startDate, days, availability, selectedStartDate, selectedEndDate, onDateClick</p>
            <div class="row">
                <div class="col-12 mb-3">
                    @php
                        $startDate = \Carbon\Carbon::now();
                        $availability = [];
                        for ($i = 0; $i < 30; $i++) {
                            $date = $startDate->copy()->addDays($i);
                            $dateKey = $date->format('Y-m-d');
                            // Przykładowe dane - dostępne w dni powszednie, niedostępne w weekendy
                            $availability[$dateKey] = [
                                'can_assign' => !$date->isWeekend(),
                                'reason' => $date->isWeekend() ? 'Weekend' : null,
                            ];
                        }
                    @endphp
                    <x-ui.cal 
                        :startDate="$startDate"
                        :days="30"
                        :availability="$availability"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-12 mb-3">
                    <p class="text-muted small mb-2">Przykład z wybranym zakresem dat:</p>
                    @php
                        $startDate2 = \Carbon\Carbon::now();
                        $availability2 = [];
                        for ($i = 0; $i < 30; $i++) {
                            $date = $startDate2->copy()->addDays($i);
                            $dateKey = $date->format('Y-m-d');
                            $availability2[$dateKey] = [
                                'can_assign' => !$date->isWeekend(),
                                'reason' => $date->isWeekend() ? 'Weekend' : null,
                            ];
                        }
                        $selectedStart = $startDate2->copy()->addDays(5);
                        $selectedEnd = $startDate2->copy()->addDays(10);
                    @endphp
                    <x-ui.cal 
                        :startDate="$startDate2"
                        :days="30"
                        :availability="$availability2"
                        :selectedStartDate="$selectedStart->format('Y-m-d')"
                        :selectedEndDate="$selectedEnd->format('Y-m-d')"
                    />
                </div>
            </div>
        </div>
        <hr>

    </div>

</x-app-layout>
