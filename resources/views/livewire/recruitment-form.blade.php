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
                        Numer telefonu
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
                <label class="form-label" for="desired_role">
                    Stanowisko, o które się ubiegasz
                </label>
                <input
                    type="text"
                    id="desired_role"
                    wire:model.blur="desired_role"
                    class="form-control @error('desired_role') is-invalid @enderror"
                    placeholder="np. Kierowca, Mechanik, Operator maszyn…"
                >
                @error('desired_role')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
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

            <div class="mb-3">
                <label class="form-label" for="cover_letter">
                    List motywacyjny / wiadomość
                </label>
                <textarea
                    id="cover_letter"
                    wire:model.blur="cover_letter"
                    class="form-control @error('cover_letter') is-invalid @enderror"
                    rows="5"
                    placeholder="Kilka słów o sobie, doświadczeniu i motywacji do pracy…"
                ></textarea>
                <div class="d-flex justify-content-between mt-1">
                    @error('cover_letter')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @else
                        <span></span>
                    @enderror
                    <small class="text-muted ms-auto">{{ mb_strlen($cover_letter) }} / 5000</small>
                </div>
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
