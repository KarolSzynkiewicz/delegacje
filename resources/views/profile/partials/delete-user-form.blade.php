<x-ui.card class="profile-danger">
    <div class="profile-danger__row">
        <div>
            <h3 class="profile-danger__title">Usuń konto</h3>
            <p class="profile-danger__copy mb-0">
                Po usunięciu konta wszystkie Twoje dane zostaną trwale usunięte. Przed usunięciem pobierz to, co chcesz zachować.
            </p>
        </div>
        <button
            type="button"
            class="btn profile-danger__btn"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >Usuń konto</button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable dark compact teleport>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-4">
            @csrf
            @method('delete')

            <h2 class="fs-5 fw-semibold mb-2">Na pewno usunąć konto?</h2>
            <p class="text-muted small">
                Tego nie da się cofnąć. Wpisz hasło, żeby potwierdzić usunięcie konta.
            </p>

            <div class="mt-3">
                <label class="form-label" for="delete-account-password">Hasło</label>
                <input
                    id="delete-account-password"
                    name="password"
                    type="password"
                    class="form-control"
                    placeholder="Hasło"
                    autocomplete="current-password"
                >
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-outline-secondary" x-on:click="$dispatch('close-modal', 'confirm-user-deletion')">
                    Anuluj
                </button>
                <button type="submit" class="btn profile-danger__btn">Usuń konto</button>
            </div>
        </form>
    </x-modal>
</x-ui.card>
