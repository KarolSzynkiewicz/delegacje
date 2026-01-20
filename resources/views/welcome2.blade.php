<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Komponenty UI - Przegląd">
        </x-ui.page-header>
    </x-slot>

    <div class="container-fluid">
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
            <p class="text-muted small">Przepisana paginacja</p>
            <nav>
                <ul class="pagination">
                    <li class="page-item disabled"><a class="page-link" href="#">Poprzednia</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Następna</a></li>
                </ul>
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
</x-app-layout>
