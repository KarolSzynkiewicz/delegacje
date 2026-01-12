<x-guest-layout>
    <section class="background-radial-gradient overflow-hidden vh-100 d-flex align-items-center">
        <div class="container px-4 py-5 px-md-5 text-center text-lg-start">
            <div class="row gx-lg-5 align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" style="z-index: 10">
                    <h1 class="my-5 display-5 fw-bold ls-tight">
                        Stocznia <br />
                        <span class="text-primary">System Zarządzania</span>
                    </h1>
                    <p class="mb-4 opacity-75 text-muted">
                        Inteligentny system do zarządzania projektami, pracownikami i logistyką. 
                        Automatyzuj przypisania, kontroluj dokumentację i oszczędzaj czas.
                    </p>
                </div>

                <div class="col-lg-6 mb-5 mb-lg-0 position-relative">
                    <div id="radius-shape-1" class="position-absolute rounded-circle shadow-5-strong"></div>
                    <div id="radius-shape-2" class="position-absolute shadow-5-strong"></div>

                    <x-ui.card class="bg-glass">
                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const emailInput = document.getElementById('email');
                                    if (emailInput) {
                                        emailInput.setAttribute('autofocus', 'autofocus');
                                        emailInput.setAttribute('autocomplete', 'username');
                                    }
                                    const passwordInput = document.getElementById('password');
                                    if (passwordInput) {
                                        passwordInput.setAttribute('autocomplete', 'current-password');
                                    }
                                });
                            </script>

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

                            <!-- Remember Me -->
                            <div class="form-check d-flex justify-content-between mb-4">
                                <div>
                                    <x-ui.input 
                                        type="checkbox" 
                                        name="remember" 
                                        id="remember_me"
                                        label="{{ __('Remember me') }}"
                                    />
                                </div>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-primary text-decoration-none">
                                        {{ __('Forgot your password?') }}
                                    </a>
                                @endif
                            </div>

                            <!-- Submit button -->
                            <x-ui.button variant="primary" type="submit" class="w-100 mb-4">
                                <i class="bi bi-box-arrow-in-right me-2"></i>{{ __('Log in') }}
                            </x-ui.button>

                            <!-- Register link -->
                            <div class="text-center">
                                <p class="mb-0">Nie masz konta? 
                                    <a href="{{ route('register') }}" class="text-primary text-decoration-none fw-bold">Zarejestruj się</a>
                                </p>
                            </div>
                        </form>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
