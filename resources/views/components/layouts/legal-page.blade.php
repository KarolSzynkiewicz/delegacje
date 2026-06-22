@props([
    'title',
    'backUrl' => null,
    'backLabel' => 'Wróć do formularza',
])

<x-layouts.public :title="$title . ' – ' . config('app.name')">
    <div class="min-vh-100 d-flex flex-column">

        <header class="py-3 px-4 border-bottom" style="background: var(--bg-card); border-color: var(--border-color) !important;">
            <div class="container-md d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-primary fs-5"></i>
                    <span class="fw-semibold">{{ config('app.name') }}</span>
                    <span class="text-muted small d-none d-sm-inline">— Informacje prawne</span>
                </div>
                <a href="{{ $backUrl ?? route('recruitment.apply') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> {{ $backLabel }}
                </a>
            </div>
        </header>

        <main class="flex-grow-1 py-5 px-3">
            <div class="container-md" style="max-width:760px;">
                <article class="card p-4 p-md-5 legal-content">
                    <h1 class="fw-semibold fs-3 mb-4">{{ $title }}</h1>
                    {{ $slot }}
                </article>
            </div>
        </main>

        <footer class="py-3 text-center" style="border-top: 1px solid var(--border-color);">
            <small class="text-muted">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </small>
        </footer>

    </div>

    @push('scripts')
    <style>
        .legal-content {
            color: var(--text-main, #f1f5f9);
        }
        .legal-content h1,
        .legal-content h2,
        .legal-content h3 {
            color: var(--text-main, #f1f5f9);
        }
        .legal-content h2 { font-size: 1.15rem; font-weight: 600; margin-top: 1.75rem; margin-bottom: 0.75rem; }
        .legal-content h3 { font-size: 1rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .legal-content p,
        .legal-content li {
            line-height: 1.7;
            color: var(--text-muted, #94a3b8);
        }
        .legal-content .lead,
        .legal-content .text-body {
            color: var(--text-main, #f1f5f9) !important;
        }
        .legal-content strong {
            color: var(--text-main, #f1f5f9);
        }
        .legal-content a {
            color: #60a5fa;
        }
        .legal-content a:hover {
            color: #93c5fd;
        }
        .legal-content ul,
        .legal-content ol {
            padding-left: 1.25rem;
        }
        .legal-content li {
            margin-bottom: 0.35rem;
        }
        .legal-content .info-box {
            background: rgba(96, 165, 250, 0.1);
            border-left: 3px solid #60a5fa;
            padding: 1rem 1.25rem;
            border-radius: 0.375rem;
            margin: 1.25rem 0;
        }
        .legal-content .info-box p,
        .legal-content .info-box strong {
            color: var(--text-main, #f1f5f9) !important;
        }
        .legal-content .info-box p:last-child {
            margin-bottom: 0;
        }
        .legal-content .info-box a {
            color: #93c5fd;
        }
    </style>
    @endpush
</x-layouts.public>
