<div>
    @if($submitted)
        {{-- Potwierdzenie wysłania --}}
        <div class="text-center py-5">
            <div class="mb-4">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success" style="width:80px;height:80px;">
                    <i class="bi bi-check-lg text-white" style="font-size:2.5rem;"></i>
                </div>
            </div>
            <h2 class="fw-semibold mb-2">Dziękujemy za zgłoszenie!</h2>
            <p class="text-muted mb-4" style="max-width:480px;margin:0 auto;">
                Twoje zgłoszenie zostało pomyślnie przesłane. Skontaktujemy się z Tobą
                pod adresem <strong>{{ $email }}</strong> po zapoznaniu się z aplikacją.
            </p>
            <a href="/rekrutacja" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Wróć do strony rekrutacji
            </a>
        </div>
    @else
        {{-- Formularz --}}
        <form wire:submit.prevent="submit" enctype="multipart/form-data" novalidate>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="first_name">
                        Imię <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="first_name"
                        wire:model.blur="first_name"
                        class="form-control @error('first_name') is-invalid @enderror"
                        placeholder="Jan"
                        autocomplete="given-name"
                    >
                    @error('first_name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="last_name">
                        Nazwisko <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="last_name"
                        wire:model.blur="last_name"
                        class="form-control @error('last_name') is-invalid @enderror"
                        placeholder="Kowalski"
                        autocomplete="family-name"
                    >
                    @error('last_name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="city">Miasto zamieszkania</label>
                    <input type="text" id="city" wire:model.blur="city"
                           class="form-control @error('city') is-invalid @enderror"
                           placeholder="np. Warszawa, Kraków…" autocomplete="address-level2">
                    @error('city')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="email">
                        Adres e-mail <span class="text-danger">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        wire:model.blur="email"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="jan@przyklad.pl"
                        autocomplete="email"
                    >
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="phone">
                        Numer telefonu <span class="text-danger">*</span>
                    </label>
                    <input
                        type="tel"
                        id="phone"
                        wire:model.blur="phone"
                        class="form-control @error('phone') is-invalid @enderror"
                        placeholder="+48 600 000 000"
                        autocomplete="tel"
                    >
                    @error('phone')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Stanowisko, o które się ubiegasz
                </label>
                <div class="border rounded p-3 @error('desired_roles') border-danger @enderror">
                    @forelse($roles as $role)
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="desired_role_{{ $role->id }}"
                                wire:model="desired_roles"
                                value="{{ $role->id }}"
                                class="form-check-input"
                            >
                            <label class="form-check-label" for="desired_role_{{ $role->id }}">
                                {{ $role->name }}
                            </label>
                        </div>
                    @empty
                        <p class="text-muted mb-0 small">Brak zdefiniowanych stanowisk.</p>
                    @endforelse
                </div>
                @error('desired_roles')
                    <span class="text-danger small d-block mt-1">{{ $message }}</span>
                @enderror
                <small class="text-muted">Możesz wybrać więcej niż jedno stanowisko.</small>
            </div>

            <div class="mb-3">
                <label class="form-label" for="expected_rate_eur">
                    Oczekiwana stawka (EUR/h)
                </label>
                <div class="input-group" style="max-width:220px;">
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="expected_rate_eur"
                        wire:model.blur="expected_rate_eur"
                        class="form-control @error('expected_rate_eur') is-invalid @enderror"
                        placeholder="np. 15.00"
                    >
                    <span class="input-group-text">EUR/h</span>
                    @error('expected_rate_eur')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Uprawnienia i języki</label>
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_driving_license_b" wire:model="has_driving_license_b">
                        <label class="form-check-label" for="has_driving_license_b">
                            <i class="bi bi-car-front me-1"></i>Prawo jazdy kat.&nbsp;B
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="speaks_english" wire:model="speaks_english">
                        <label class="form-check-label" for="speaks_english">🇬🇧 Angielski</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="speaks_french" wire:model="speaks_french">
                        <label class="form-check-label" for="speaks_french">🇫🇷 Francuski</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="speaks_german" wire:model="speaks_german">
                        <label class="form-check-label" for="speaks_german">🇩🇪 Niemiecki</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="referral_source">
                    Skąd o nas wiesz?
                </label>
                <select
                    id="referral_source"
                    wire:model.blur="referral_source"
                    class="form-select @error('referral_source') is-invalid @enderror"
                >
                    <option value="">— Wybierz opcję —</option>
                    @foreach(\App\Enums\RecruitmentReferralSource::options() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('referral_source')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label" for="photo">
                    Zdjęcie (opcjonalne)
                </label>

                <div>
                    <input
                        type="file"
                        id="photo"
                        wire:model="photo"
                        class="form-control @error('photo') is-invalid @enderror"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    <small class="text-muted">Dozwolone formaty: JPG, PNG, WebP. Maksymalny rozmiar: 2 MB.</small>
                    @error('photo')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                @if($photo)
                    <div class="mt-2">
                        <img
                            src="{{ $photo->temporaryUrl() }}"
                            alt="Podgląd zdjęcia"
                            class="rounded"
                            style="max-height:120px;max-width:120px;object-fit:cover;"
                        >
                    </div>
                @endif
            </div>

            {{-- Zgody --}}
            <div class="mb-4 pt-2 border-top">
                <h3 class="fs-6 fw-semibold mb-3">Zgody i oświadczenia</h3>

                <div class="d-flex flex-column gap-3">

                    {{-- RODO --}}
                    <div class="border rounded p-3 @error('consent_rodo') border-danger @enderror">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="consent_rodo"
                                wire:model="consent_rodo"
                                class="form-check-input @error('consent_rodo') is-invalid @enderror"
                            >
                            <label class="form-check-label" for="consent_rodo">
                                Wyrażam zgodę na przetwarzanie moich danych osobowych zgodnie z
                                <a href="{{ route('recruitment.rodo') }}" target="_blank" rel="noopener" class="fw-semibold">
                                    informacją RODO <i class="bi bi-box-arrow-up-right small"></i>
                                </a>
                                <span class="text-danger">*</span>
                            </label>
                        </div>
                        @error('consent_rodo')
                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                        @enderror
                        <button
                            type="button"
                            class="btn btn-link btn-sm p-0 mt-1 text-decoration-none"
                            data-bs-toggle="collapse"
                            data-bs-target="#preview-rodo"
                            aria-expanded="false"
                        >
                            <i class="bi bi-chevron-down me-1"></i> Podgląd warunków
                        </button>
                        <div class="collapse mt-2" id="preview-rodo">
                            <div class="small text-muted border-start border-3 border-primary ps-3 py-1">
                                Administrator przetwarza dane w celu przeprowadzenia rekrutacji.
                                Masz prawo dostępu, sprostowania, usunięcia danych i wycofania zgody.
                                <a href="{{ route('recruitment.rodo') }}" target="_blank" rel="noopener">Pełna treść informacji RODO →</a>
                            </div>
                        </div>
                    </div>

                    {{-- Rekrutacja bieżąca i przyszła --}}
                    <div class="border rounded p-3 @error('consent_recruitment_processing') border-danger @enderror">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="consent_recruitment_processing"
                                wire:model="consent_recruitment_processing"
                                class="form-check-input @error('consent_recruitment_processing') is-invalid @enderror"
                            >
                            <label class="form-check-label" for="consent_recruitment_processing">
                                Wyrażam zgodę na przetwarzanie moich danych w celu
                                <strong>tej i przyszłych rekrutacji</strong>
                                <a href="{{ route('recruitment.consent.recruitment') }}" target="_blank" rel="noopener" class="fw-semibold">
                                    (szczegóły) <i class="bi bi-box-arrow-up-right small"></i>
                                </a>
                                <span class="text-danger">*</span>
                            </label>
                        </div>
                        @error('consent_recruitment_processing')
                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                        @enderror
                        <button
                            type="button"
                            class="btn btn-link btn-sm p-0 mt-1 text-decoration-none"
                            data-bs-toggle="collapse"
                            data-bs-target="#preview-recruitment"
                            aria-expanded="false"
                        >
                            <i class="bi bi-chevron-down me-1"></i> Jak to działa?
                        </button>
                        <div class="collapse mt-2" id="preview-recruitment">
                            <div class="small text-muted border-start border-3 border-primary ps-3 py-1">
                                Twoje dane będą wykorzystane w bieżącym procesie rekrutacyjnym.
                                Możemy też zachować je na potrzeby przyszłych ofert pracy i skontaktować się,
                                gdy pojawi się odpowiednie stanowisko. Zgodę możesz wycofać w dowolnym momencie.
                                <a href="{{ route('recruitment.consent.recruitment') }}" target="_blank" rel="noopener">Pełny opis zgody →</a>
                            </div>
                        </div>
                    </div>

                    {{-- Marketing --}}
                    <div class="border rounded p-3">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="consent_marketing"
                                wire:model="consent_marketing"
                                class="form-check-input"
                            >
                            <label class="form-check-label" for="consent_marketing">
                                Wyrażam zgodę marketingową na otrzymywanie informacji o ofertach pracy
                                i działalności firmy
                                <a href="{{ route('recruitment.consent.marketing') }}" target="_blank" rel="noopener" class="fw-semibold">
                                    (szczegóły) <i class="bi bi-box-arrow-up-right small"></i>
                                </a>
                                <span class="text-muted small">(opcjonalnie)</span>
                            </label>
                        </div>
                        <button
                            type="button"
                            class="btn btn-link btn-sm p-0 mt-1 text-decoration-none"
                            data-bs-toggle="collapse"
                            data-bs-target="#preview-marketing"
                            aria-expanded="false"
                        >
                            <i class="bi bi-chevron-down me-1"></i> Jak to działa?
                        </button>
                        <div class="collapse mt-2" id="preview-marketing">
                            <div class="small text-muted border-start border-3 border-secondary ps-3 py-1">
                                Zgoda jest dobrowolna i nie wpływa na rozpatrzenie kandydatury.
                                Możesz otrzymywać e-maile lub SMS-y o nowych ofertach pracy.
                                W każdej chwili możesz zrezygnować z komunikacji marketingowej.
                                <a href="{{ route('recruitment.consent.marketing') }}" target="_blank" rel="noopener">Pełny opis zgody →</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="d-flex align-items-center gap-3 pt-2">
                <button
                    type="submit"
                    class="btn btn-primary px-4"
                    wire:loading.attr="disabled"
                    wire:target="submit,photo"
                >
                    <span wire:loading.remove wire:target="submit">
                        <i class="bi bi-send me-1"></i> Wyślij zgłoszenie
                    </span>
                    <span wire:loading wire:target="submit">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Wysyłanie…
                    </span>
                </button>

                <small class="text-muted">
                    Pola oznaczone <span class="text-danger">*</span> są wymagane.
                </small>
            </div>

            {{-- Upload progress indicator --}}
            <div wire:loading wire:target="photo" class="mt-2">
                <small class="text-muted">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Ładowanie zdjęcia…
                </small>
            </div>

        </form>
    @endif
</div>
