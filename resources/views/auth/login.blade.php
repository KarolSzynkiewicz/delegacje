<x-guest-layout>
    <section class="background-radial-gradient overflow-hidden vh-100 d-flex align-items-center">
        <style>
            .background-radial-gradient {
                background-color: hsl(218, 41%, 15%);
                background-image: radial-gradient(650px circle at 0% 0%,
                        hsl(218, 41%, 35%) 15%,
                        hsl(218, 41%, 30%) 35%,
                        hsl(218, 41%, 20%) 75%,
                        hsl(218, 41%, 19%) 80%,
                        transparent 100%),
                    radial-gradient(1250px circle at 100% 100%,
                        hsl(218, 41%, 45%) 15%,
                        hsl(218, 41%, 30%) 35%,
                        hsl(218, 41%, 20%) 75%,
                        hsl(218, 41%, 19%) 80%,
                        transparent 100%);
            }

            #radius-shape-1 {
                height: 220px;
                width: 220px;
                top: -60px;
                left: -130px;
                background: radial-gradient(#44006b, #ad1fff);
                overflow: hidden;
            }

            #radius-shape-2 {
                border-radius: 38% 62% 63% 37% / 70% 33% 67% 30%;
                bottom: -60px;
                right: -110px;
                width: 300px;
                height: 300px;
                background: radial-gradient(#44006b, #ad1fff);
                overflow: hidden;
            }

            .bg-glass {
                background-color: hsla(0, 0%, 100%, 0.9) !important;
                backdrop-filter: saturate(200%) blur(25px);
            }
        </style>

        <div class="container px-4 py-5 px-md-5 text-center text-lg-start">
            <div class="row gx-lg-5 align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" style="z-index: 10">
                    <h1 class="my-5 display-5 fw-bold ls-tight" style="color: hsl(218, 81%, 95%)">
                        Stocznia <br />
                        <span style="color: hsl(218, 81%, 75%)">System Zarządzania</span>
                    </h1>
                    <p class="mb-4 opacity-75" style="color: hsl(218, 81%, 85%)">
                        Inteligentny system do zarządzania projektami, pracownikami i logistyką. 
                        Automatyzuj przypisania, kontroluj dokumentację i oszczędzaj czas.
                    </p>
                </div>

                <div class="col-lg-6 mb-5 mb-lg-0 position-relative">
                    <div id="radius-shape-1" class="position-absolute rounded-circle shadow-5-strong"></div>
                    <div id="radius-shape-2" class="position-absolute shadow-5-strong"></div>

                    <div class="card bg-glass">
                        <div class="card-body px-4 py-5 px-md-5">
                            <!-- Session Status -->
                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('status') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <!-- Email Address -->
                                <div class="form-outline mb-4">
                                    <label class="form-label" for="email">{{ __('Email') }}</label>
                                    <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                           value="{{ old('email') }}" required autofocus autocomplete="username" />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Password -->
                                <div class="form-outline mb-4">
                                    <label class="form-label" for="password">{{ __('Password') }}</label>
                                    <input type="password" id="password" name="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           required autocomplete="current-password" />
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Remember Me -->
                                <div class="form-check d-flex justify-content-between mb-4">
                                    <div>
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember_me" />
                                        <label class="form-check-label" for="remember_me">
                                            {{ __('Remember me') }}
                                        </label>
                                    </div>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-decoration-none">
                                            {{ __('Forgot your password?') }}
                                        </a>
                                    @endif
                                </div>

                                <!-- Submit button -->
                                <button type="submit" class="btn btn-primary btn-block mb-4 w-100">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>{{ __('Log in') }}
                                </button>

                                <!-- Register link -->
                                <div class="text-center">
                                    <p class="mb-0">Nie masz konta? 
                                        <a href="{{ route('register') }}" class="text-decoration-none fw-bold">Zarejestruj się</a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
