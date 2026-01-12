@php
    $title = 'Visual Concept Sandbox';
@endphp

@extends('ui-concept.layout')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="fw-bold display-4 mb-2">
            STOCZNIA <span class="text-primary">PRO</span> - VISUAL CONCEPT SANDBOX
        </h1>
        <p class="text-muted">Kompletna biblioteka komponentów dla widoku tygodniowego i zarządzania ekipą</p>
        <p class="text-danger fw-bold mt-3">To jest jedyny dozwolony zestaw komponentów UI w projekcie.</p>
    </div>

    <!-- Przyciski -->
    <h2 class="section-title">Buttons & Badges</h2>
    <div class="row g-4 align-items-center">
        <div class="col-md-6">
            <x-ui.card label="Akcje główne - wszystkie z efektem rozjaśnienia na hover">
                <div class="d-flex flex-wrap gap-3">
                    <x-ui.button variant="primary">
                        <i class="bi bi-plus-lg"></i> Dodaj osobę
                    </x-ui.button>
                    <x-ui.button variant="ghost">
                        <i class="bi bi-pencil"></i> Edytuj
                    </x-ui.button>
                    <x-ui.button variant="danger">
                        <i class="bi bi-trash"></i> Usuń
                    </x-ui.button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Najedź myszką na przyciski, aby zobaczyć efekt rozjaśnienia i uniesienia
                    </small>
                </div>
            </x-ui.card>
        </div>
        <div class="col-md-6">
            <x-ui.card label="Statusy / Badges">
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <x-ui.badge variant="success">
                        <i class="bi bi-check-circle-fill"></i> sukces
                    </x-ui.badge>
                    <x-ui.badge variant="danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Braki 
                    </x-ui.badge>
                    <x-ui.badge variant="accent">
                        Elektryk
                    </x-ui.badge>
                    <x-ui.badge variant="info">
                        Operator
                    </x-ui.badge>
                    <x-ui.badge variant="warning">
                        <i class="bi bi-clock"></i> uwagi
                    </x-ui.badge>
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Stats & Infocards -->
    <h2 class="section-title">Stats & Infocards</h2>
    <div class="row g-4">
        <div class="col-md-3">
            <x-ui.card>
                <span class="card-label">Realizacja</span>
                <div class="stat-value">50%</div>
                <x-ui.progress value="50" max="100" />
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card>
                <span class="card-label">Aktywni Pracownicy</span>
                <div class="stat-value">24<small class="text-muted">/30</small></div>
                <div class="text-success small fw-bold mt-1">
                    <i class="bi bi-arrow-up"></i> +2 dzisiaj
                </div>
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card>
                <span class="card-label">Pojazdy</span>
                <div class="stat-value">8<small class="text-muted">/10</small></div>
                <div class="text-info small fw-bold mt-1">
                    <i class="bi bi-car-front"></i> Dostępne
                </div>
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card>
                <span class="card-label">Mieszkania</span>
                <div class="stat-value">12<small class="text-muted">/15</small></div>
                <div class="text-warning small fw-bold mt-1">
                    <i class="bi bi-house"></i> 3 wolne
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <x-ui.alert variant="danger" title="Alert Logistyczny">
                Mieszkanie 1 jest przepełnione (2/2). Musisz przypisać Robertowi nowe lokum.
            </x-ui.alert>
        </div>
        <div class="col-md-6">
            <x-ui.alert variant="success" title="Status OK">
                Wszyscy pracownicy mają przypisane pojazdy i zakwaterowanie.
            </x-ui.alert>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <x-ui.alert variant="warning" title="Ostrzeżenie">
                Częściowe pokrycie – wymaga uwagi
            </x-ui.alert>
        </div>
    </div>

    <!-- Complex Data Table -->
    <h2 class="section-title">Complex Data Table</h2>
    <x-ui.card class="p-0 overflow-hidden">
        <table class="table">
            <thead>
                <tr>
                    <th>PRACOWNIK</th>
                    <th>ROLA</th>
                    <th>POKRYCIE</th>
                    <th>ZAKWATEROWANIE</th>
                    <th class="text-end">AKCJA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-ui">RJ</div>
                            <div>
                                <div class="fw-bold">Robert Jaworski</div>
                                <div class="text-muted small">GDA 75318</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <x-ui.badge variant="info">Operator</x-ui.badge>
                    </td>
                    <td>
                        <div class="fw-bold">6/7 dni</div>
                        <div class="text-muted small">pon-sob</div>
                    </td>
                    <td>
                        <i class="bi bi-house-door me-2"></i>Mieszkanie 1
                    </td>
                    <td class="text-end">
                        <x-ui.button variant="ghost">
                            <i class="bi bi-chevron-right"></i>
                        </x-ui.button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-ui">JP</div>
                            <div>
                                <div class="fw-bold">Justyna Piotrowska</div>
                                <div class="text-muted small">GDA 99122</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <x-ui.badge variant="accent">Elektryk</x-ui.badge>
                    </td>
                    <td>
                        <div class="fw-bold text-warning">5/7 dni</div>
                    </td>
                    <td>
                        <span class="text-danger small">Brak przypisania</span>
                    </td>
                    <td class="text-end">
                        <x-ui.button variant="ghost">
                            <i class="bi bi-chevron-right"></i>
                        </x-ui.button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-ui">MA</div>
                            <div>
                                <div class="fw-bold">Marek Adamczyk</div>
                                <div class="text-muted small">GDA 44221</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <x-ui.badge variant="warning">Zmienne</x-ui.badge>
                    </td>
                    <td>
                        <div class="fw-bold">7/7 dni</div>
                        <div class="text-muted small">cały tydzień</div>
                    </td>
                    <td>
                        <i class="bi bi-house-door me-2"></i>Mieszkanie 2
                    </td>
                    <td class="text-end">
                        <x-ui.button variant="ghost">
                            <i class="bi bi-chevron-right"></i>
                        </x-ui.button>
                    </td>
                </tr>
            </tbody>
        </table>
    </x-ui.card>

    <!-- Forms & Inputs -->
    <h2 class="section-title">Forms & Inputs</h2>
    <div class="row g-4">
        <div class="col-md-8">
            <x-ui.card label="Edycja przypisania">
                <form>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <x-ui.input 
                                type="select" 
                                name="employee" 
                                label="Pracownik"
                            >
                                <option value="">Wybierz pracownika</option>
                                <option value="1">Marek Adamczyk</option>
                                <option value="2">Robert Jaworski</option>
                                <option value="3">Justyna Piotrowska</option>
                            </x-ui.input>
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="text" 
                                name="vehicle" 
                                label="Pojazd"
                                placeholder="np. Mercedes Sprinter WA 12345"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="start_date" 
                                label="Data rozpoczęcia"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="end_date" 
                                label="Data zakończenia"
                                required="true"
                            />
                        </div>
                        <div class="col-12">
                            <x-ui.input 
                                type="textarea" 
                                name="notes" 
                                label="Uwagi do rotacji"
                                placeholder="Dodatkowe informacje dotyczące przypisania..."
                                rows="4"
                            />
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-3 pt-2">
                                <x-ui.button variant="ghost" type="button">
                                    Anuluj
                                </x-ui.button>
                                <x-ui.button variant="primary" type="submit">
                                    <i class="bi bi-check-lg"></i> Zapisz zmiany
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </form>
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card label="Szybki filtr" class="h-100">
                <div class="form-check">
                    <input type="checkbox" id="c1" checked>
                    <label for="c1">Tylko braki kadrowe</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="c2">
                    <label for="c2">Bez zakwaterowania</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="c3">
                    <label for="c3">Bez pojazdu</label>
                </div>
                <hr>
                <x-ui.input 
                    type="date" 
                    name="date_from" 
                    label="Zakres dat od"
                />
                <div class="mt-3">
                    <x-ui.input 
                        type="date" 
                        name="date_to" 
                        label="Zakres dat do"
                    />
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Progress Bars -->
    <h2 class="section-title">Progress Bars</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <x-ui.card label="Realizacja projektu">
                <x-ui.progress value="75" max="100" showLabel="true" />
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card label="Pokrycie kadrowe">
                <x-ui.progress value="90" max="100" showLabel="true" variant="success" />
            </x-ui.card>
        </div>
        <div class="col-md-4">
            <x-ui.card label="Ostrzeżenie">
                <x-ui.progress value="45" max="100" showLabel="true" variant="warning" />
            </x-ui.card>
        </div>
    </div>

    <!-- Navigation -->
    <h2 class="section-title">Navigation</h2>
    <x-ui.navbar brand="Stocznia PRO" brandUrl="#">
        <a href="#" class="nav-link active">
            <i class="bi bi-calendar-week"></i> Przegląd
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-people"></i> Ekipa
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-car-front"></i> Pojazdy
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-house"></i> Mieszkania
        </a>
    </x-ui.navbar>

    <!-- ============================================ -->
    <!-- NOWE KOMPONENTY - DO PRZEGLĄDU -->
    <!-- ============================================ -->
    <hr class="my-5" style="border-color: var(--glass-border); opacity: 0.3;">

    <!-- Typography & Text Styles -->
    <h2 class="section-title">Typography & Text Styles</h2>
    <x-ui.card label="Hierarchia tekstu">
        <div class="mb-3">
            <h1 class="display-1">Display 1</h1>
            <h1>Heading 1</h1>
            <h2>Heading 2</h2>
            <h3>Heading 3</h3>
            <h4>Heading 4</h4>
            <h5>Heading 5</h5>
            <h6>Heading 6</h6>
            <p class="lead">Lead paragraph - większy tekst wprowadzający</p>
            <p>Normalny paragraf tekstu. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
            <p class="small">Small text - mniejszy tekst pomocniczy</p>
        </div>
        <div class="mt-4">
            <div class="mb-2"><span class="fw-bold">Bold</span> - tekst pogrubiony</div>
            <div class="mb-2"><span class="fw-semibold">Semibold</span> - tekst średnio pogrubiony</div>
            <div class="mb-2"><span class="fw-normal">Normal</span> - tekst normalny</div>
            <div class="mb-2"><span class="fst-italic">Italic</span> - tekst pochylony</div>
            <div class="mb-2"><span class="text-uppercase">Uppercase</span> - tekst wielkimi literami</div>
            <div class="mb-2"><span class="text-lowercase">LOWERCASE</span> - tekst małymi literami</div>
        </div>
    </x-ui.card>

    <!-- Text Colors -->
    <h2 class="section-title">Text Colors</h2>
    <x-ui.card label="Kolory tekstu">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="mb-2"><span class="text-primary">text-primary</span> - kolor główny</div>
                <div class="mb-2"><span class="text-accent">text-accent</span> - kolor akcentu</div>
                <div class="mb-2"><span class="text-success">text-success</span> - sukces</div>
                <div class="mb-2"><span class="text-warning">text-warning</span> - ostrzeżenie</div>
                <div class="mb-2"><span class="text-danger">text-danger</span> - błąd/niebezpieczeństwo</div>
            </div>
            <div class="col-md-6">
                <div class="mb-2"><span class="text-muted">text-muted</span> - tekst przygaszony</div>
                <div class="mb-2"><span class="text-info">text-info</span> - informacja</div>
                <div class="mb-2"><span style="color: var(--text-main);">text-main</span> - główny kolor tekstu</div>
            </div>
        </div>
    </x-ui.card>

    <!-- Breadcrumbs -->
    <h2 class="section-title">Breadcrumbs</h2>
    <x-ui.card>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb-ui">
                <li class="breadcrumb-item-ui"><a href="#">Strona główna</a></li>
                <li class="breadcrumb-item-ui"><a href="#">Projekty</a></li>
                <li class="breadcrumb-item-ui active" aria-current="page">Szczegóły projektu</li>
            </ol>
        </nav>
    </x-ui.card>

    <!-- Dropdown -->
    <h2 class="section-title">Dropdown Menu</h2>
    <x-ui.card label="Menu rozwijane">
        <div class="d-flex gap-3 flex-wrap">
            <div class="dropdown-ui">
                <button class="btn btn-ghost dropdown-toggle-ui" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Akcje <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="dropdown-menu-ui">
                    <li><a class="dropdown-item-ui" href="#"><i class="bi bi-pencil"></i> Edytuj</a></li>
                    <li><a class="dropdown-item-ui" href="#"><i class="bi bi-copy"></i> Duplikuj</a></li>
                    <li><hr class="dropdown-divider-ui"></li>
                    <li><a class="dropdown-item-ui text-danger" href="#"><i class="bi bi-trash"></i> Usuń</a></li>
                </ul>
            </div>
            <div class="dropdown-ui">
                <button class="btn btn-primary dropdown-toggle-ui" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Dodaj <i class="bi bi-plus-lg"></i>
                </button>
                <ul class="dropdown-menu-ui">
                    <li><a class="dropdown-item-ui" href="#"><i class="bi bi-person-plus"></i> Pracownik</a></li>
                    <li><a class="dropdown-item-ui" href="#"><i class="bi bi-car-front"></i> Pojazd</a></li>
                    <li><a class="dropdown-item-ui" href="#"><i class="bi bi-house"></i> Mieszkanie</a></li>
                </ul>
            </div>
        </div>
    </x-ui.card>

    <!-- Tabs -->
    <h2 class="section-title">Tabs</h2>
    <x-ui.card>
        <ul class="nav nav-tabs-ui" role="tablist">
            <li class="nav-item-ui" role="presentation">
                <button class="nav-link-ui active" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab">
                    <i class="bi bi-people"></i> Pracownicy
                </button>
            </li>
            <li class="nav-item-ui" role="presentation">
                <button class="nav-link-ui" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab">
                    <i class="bi bi-car-front"></i> Pojazdy
                </button>
            </li>
            <li class="nav-item-ui" role="presentation">
                <button class="nav-link-ui" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab">
                    <i class="bi bi-house"></i> Mieszkania
                </button>
            </li>
        </ul>
        <div class="tab-content-ui mt-3">
            <div class="tab-pane-ui active" id="tab1" role="tabpanel">
                <p>Zawartość zakładki Pracownicy. Lista wszystkich przypisanych pracowników do projektu.</p>
            </div>
            <div class="tab-pane-ui" id="tab2" role="tabpanel">
                <p>Zawartość zakładki Pojazdy. Lista wszystkich pojazdów przypisanych do projektu.</p>
            </div>
            <div class="tab-pane-ui" id="tab3" role="tabpanel">
                <p>Zawartość zakładki Mieszkania. Lista wszystkich mieszkań przypisanych do projektu.</p>
            </div>
        </div>
    </x-ui.card>

    <!-- Pagination -->
    <h2 class="section-title">Pagination</h2>
    <x-ui.card label="Paginacja">
        <nav aria-label="Page navigation">
            <ul class="pagination-ui">
                <li class="page-item-ui">
                    <a class="page-link-ui" href="#" aria-label="Previous">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <li class="page-item-ui"><a class="page-link-ui" href="#">1</a></li>
                <li class="page-item-ui active"><a class="page-link-ui" href="#">2</a></li>
                <li class="page-item-ui"><a class="page-link-ui" href="#">3</a></li>
                <li class="page-item-ui"><a class="page-link-ui" href="#">4</a></li>
                <li class="page-item-ui"><a class="page-link-ui" href="#">5</a></li>
                <li class="page-item-ui">
                    <a class="page-link-ui" href="#" aria-label="Next">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="mt-4">
            <p class="text-muted small mb-2">Paginacja z większą ilością stron:</p>
            <nav aria-label="Page navigation">
                <ul class="pagination-ui">
                    <li class="page-item-ui">
                        <a class="page-link-ui" href="#" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <li class="page-item-ui"><a class="page-link-ui" href="#">1</a></li>
                    <li class="page-item-ui"><a class="page-link-ui" href="#">2</a></li>
                    <li class="page-item-ui active"><a class="page-link-ui" href="#">3</a></li>
                    <li class="page-item-ui"><a class="page-link-ui" href="#">4</a></li>
                    <li class="page-item-ui"><a class="page-link-ui" href="#">5</a></li>
                    <li class="page-item-ui"><a class="page-link-ui" href="#">...</a></li>
                    <li class="page-item-ui"><a class="page-link-ui" href="#">15</a></li>
                    <li class="page-item-ui">
                        <a class="page-link-ui" href="#" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="mt-4">
            <p class="text-muted small mb-2">Paginacja z disabled:</p>
            <nav aria-label="Page navigation">
                <ul class="pagination-ui">
                    <li class="page-item-ui disabled">
                        <a class="page-link-ui" href="#" aria-label="Previous" tabindex="-1">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <li class="page-item-ui active"><a class="page-link-ui" href="#">1</a></li>
                    <li class="page-item-ui"><a class="page-link-ui" href="#">2</a></li>
                    <li class="page-item-ui"><a class="page-link-ui" href="#">3</a></li>
                    <li class="page-item-ui">
                        <a class="page-link-ui" href="#" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </x-ui.card>

    <!-- Tooltips & Popovers -->
    <h2 class="section-title">Tooltips & Popovers</h2>
    <x-ui.card label="Tooltips i popover">
        <div class="d-flex gap-3 flex-wrap">
            <button type="button" class="btn btn-ghost" data-bs-toggle="tooltip" data-bs-placement="top" title="Tooltip na górze">
                Tooltip Top
            </button>
            <button type="button" class="btn btn-ghost" data-bs-toggle="tooltip" data-bs-placement="right" title="Tooltip po prawej">
                Tooltip Right
            </button>
            <button type="button" class="btn btn-ghost" data-bs-toggle="popover" data-bs-title="Tytuł popover" data-bs-content="To jest zawartość popover z dodatkowymi informacjami.">
                Popover
            </button>
        </div>
    </x-ui.card>

    <!-- List Group -->
    <h2 class="section-title">List Group</h2>
    <div class="row g-4">
        <div class="col-md-6">
            <x-ui.card label="Lista elementów">
                <ul class="list-group-ui">
                    <li class="list-group-item-ui d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">Marek Adamczyk</div>
                            <div class="text-muted small">Elektryk</div>
                        </div>
                        <x-ui.badge variant="success">Aktywny</x-ui.badge>
                    </li>
                    <li class="list-group-item-ui d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">Robert Jaworski</div>
                            <div class="text-muted small">Operator</div>
                        </div>
                        <x-ui.badge variant="info">Przypisany</x-ui.badge>
                    </li>
                    <li class="list-group-item-ui d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">Justyna Piotrowska</div>
                            <div class="text-muted small">Kierownik</div>
                        </div>
                        <x-ui.badge variant="warning">Oczekuje</x-ui.badge>
                    </li>
                </ul>
            </x-ui.card>
        </div>
        <div class="col-md-6">
            <x-ui.card label="Lista z akcjami">
                <div class="list-group-ui">
                    <a href="#" class="list-group-item-ui list-group-item-action-ui">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Projekt Stocznia Gdańsk</h6>
                            <small class="text-muted">3 dni temu</small>
                        </div>
                        <p class="mb-1 text-muted small">Aktualizacja przypisań dla tygodnia 05.01-11.01</p>
                    </a>
                    <a href="#" class="list-group-item-ui list-group-item-action-ui">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Nowy pojazd dodany</h6>
                            <small class="text-muted">5 dni temu</small>
                        </div>
                        <p class="mb-1 text-muted small">Mercedes Sprinter WA 12345 został dodany do systemu</p>
                    </a>
                    <a href="#" class="list-group-item-ui list-group-item-action-ui">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Rotacja pracownika</h6>
                            <small class="text-muted">1 tydzień temu</small>
                        </div>
                        <p class="mb-1 text-muted small">Marek Adamczyk zakończył rotację</p>
                    </a>
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Spacing & Layout -->
    <h2 class="section-title">Spacing & Layout Utilities</h2>
    <x-ui.card label="Marginesy i paddingi">
        <div class="mb-3 p-3" style="background: rgba(255,255,255,0.05); border-radius: 8px;">
            <code>.mb-3 .p-3</code> - margin-bottom i padding
        </div>
        <div class="mt-2 mb-2 px-4 py-2" style="background: rgba(255,255,255,0.05); border-radius: 8px;">
            <code>.mt-2 .mb-2 .px-4 .py-2</code> - różne marginesy i paddingi
        </div>
        <div class="d-flex gap-3 mt-3">
            <div class="flex-grow-1 p-2" style="background: rgba(255,255,255,0.05); border-radius: 8px;">
                <code>.flex-grow-1</code>
            </div>
            <div class="p-2" style="background: rgba(255,255,255,0.05); border-radius: 8px;">
                <code>Fixed</code>
            </div>
        </div>
    </x-ui.card>
</div>

<script>
    // Initialize Bootstrap tooltips and popovers
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
</script>
@endsection
