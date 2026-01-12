<x-guest-layout>
    <section class="background-radial-gradient overflow-hidden vh-100 d-flex align-items-center">
        <div class="container px-4 py-5 px-md-5 text-center text-lg-start">
            <div class="row gx-lg-5 align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" style="z-index: 10">
                    <h1 class="my-5 display-5 fw-bold ls-tight">
                        Dołącz do Stoczni <br />
                        <span class="text-primary">Zacznij już dziś</span>
                    </h1>
                    <p class="mb-4 opacity-75 text-muted">
                        Utwórz konto i rozpocznij zarządzanie projektami, pracownikami i logistyką 
                        w jednym miejscu. Automatyzuj, kontroluj, oszczędzaj czas.
                    </p>
                </div>

                <div class="col-lg-6 mb-5 mb-lg-0 position-relative">
                    <div id="radius-shape-1" class="position-absolute rounded-circle shadow-5-strong"></div>
                    <div id="radius-shape-2" class="position-absolute shadow-5-strong"></div>

                    <x-ui.card class="bg-glass">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const nameInput = document.getElementById('name');
                                    if (nameInput) {
                                        nameInput.setAttribute('autofocus', 'autofocus');
                                        nameInput.setAttribute('autocomplete', 'name');
                                    }
                                    const emailInput = document.getElementById('email');
                                    if (emailInput) {
                                        emailInput.setAttribute('autocomplete', 'username');
                                    }
                                    const passwordInput = document.getElementById('password');
                                    if (passwordInput) {
                                        passwordInput.setAttribute('autocomplete', 'new-password');
                                    }
                                    const passwordConfirmationInput = document.getElementById('password_confirmation');
                                    if (passwordConfirmationInput) {
                                        passwordConfirmationInput.setAttribute('autocomplete', 'new-password');
                                    }
                                });
                            </script>

                            <!-- Name -->
                            <div class="mb-3">
                                <x-ui.input 
                                    type="text" 
                                    name="name" 
                                    id="name"
                                    label="{{ __('Name') }}"
                                    value="{{ old('name') }}"
                                    required="true"
                                />
                            </div>

                            <!-- Email Address -->
                            <div class="mb-3">
                                <x-ui.input 
                                    type="email" 
                                    name="email" 
                                    id="email"
                                    label="{{ __('Email') }}"
                                    value="{{ old('email') }}"
                                    required="true"
                                />
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <x-ui.input 
                                    type="password" 
                                    name="password" 
                                    id="password"
                                    label="{{ __('Password') }}"
                                    required="true"
                                />
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <x-ui.input 
                                    type="password" 
                                    name="password_confirmation" 
                                    id="password_confirmation"
                                    label="{{ __('Confirm Password') }}"
                                    required="true"
                                />
                            </div>

                            <!-- Submit button -->
                            <x-ui.button variant="primary" type="submit" class="w-100 mb-4">
                                <i class="bi bi-person-plus me-2"></i>{{ __('Register') }}
                            </x-ui.button>

                            <!-- Login link -->
                            <div class="text-center">
                                <p class="mb-0">Masz już konto? 
                                    <a href="{{ route('login') }}" class="text-primary text-decoration-none fw-bold">Zaloguj się</a>
                                </p>
                            </div>
                        </form>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
