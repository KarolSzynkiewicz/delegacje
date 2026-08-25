<?php

namespace App\Livewire;

use App\Contracts\Llm\LlmCredentialRepository;
use App\Exceptions\LlmException;
use App\Models\LlmCredential;
use App\Models\User;
use App\Services\Llm\LlmManager;
use App\Services\RoutePermissionService;
use Livewire\Component;

/**
 * Konfiguracja dostawcy AI w Akcjach systemowych.
 *
 * Komponent nie zna żadnego konkretnego API — operuje na rejestrze dostawców
 * z config/llm.php, repozytorium kluczy i handlerze LlmManager.
 */
class LlmProviderSettings extends Component
{
    public string $provider = '';

    public string $apiKey = '';

    public string $model = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?string $testOutput = null;

    public function mount(): void
    {
        $this->authorizeAccess();

        $this->provider = app(LlmCredentialRepository::class)->activeProvider()
            ?? (string) config('llm.default');

        $this->loadProviderState();
    }

    public function updatedProvider(): void
    {
        $this->reset(['apiKey', 'successMessage', 'errorMessage', 'testOutput']);
        $this->loadProviderState();
    }

    public function save(): void
    {
        $this->authorizeAccess();
        $this->reset(['successMessage', 'errorMessage', 'testOutput']);

        $this->validate([
            'provider' => ['required', 'string', 'in:'.implode(',', $this->providerKeys())],
            'apiKey' => ['nullable', 'string', 'min:10', 'max:500'],
            'model' => ['nullable', 'string', 'max:100'],
        ], [], [
            'apiKey' => 'klucz API',
            'model' => 'model',
        ]);

        $repository = app(LlmCredentialRepository::class);
        $model = $this->model !== '' ? $this->model : null;

        if ($this->apiKey !== '') {
            $repository->store($this->provider, trim($this->apiKey), $model, auth()->id());
        } elseif ($this->storedCredential()) {
            $repository->updateModel($this->provider, $model);
        } else {
            $this->errorMessage = 'Podaj klucz API — dla tego dostawcy nie ma jeszcze nic zapisanego.';

            return;
        }

        $repository->activate($this->provider);

        $this->apiKey = '';
        $this->successMessage = 'Zapisano konfigurację dostawcy. Klucz jest zaszyfrowany w bazie.';
    }

    public function testConnection(): void
    {
        $this->authorizeAccess();
        $this->reset(['successMessage', 'errorMessage', 'testOutput']);

        try {
            $response = app(LlmManager::class)->ping($this->provider);

            $tokens = $response->totalTokens();

            $this->testOutput = sprintf(
                'Model %s odpowiedział: „%s”%s',
                $response->model,
                trim($response->text),
                $tokens !== null ? " (tokeny: {$tokens})" : '',
            );
        } catch (LlmException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Throwable $e) {
            $this->errorMessage = 'Nieoczekiwany błąd testu: '.$e->getMessage();
        }
    }

    public function removeKey(): void
    {
        $this->authorizeAccess();
        $this->reset(['successMessage', 'errorMessage', 'testOutput']);

        app(LlmCredentialRepository::class)->forget($this->provider);

        $this->apiKey = '';
        $this->successMessage = 'Klucz został usunięty z bazy.';
        $this->loadProviderState();
    }

    public function render()
    {
        $manager = app(LlmManager::class);
        $repository = app(LlmCredentialRepository::class);
        $credentials = $repository->find($this->provider);
        $stored = $this->storedCredential();

        return view('livewire.llm-provider-settings', [
            'providers' => $manager->providers(),
            'currentProvider' => $manager->provider($this->provider),
            'credentials' => $credentials,
            'stored' => $stored,
            'activeProvider' => $repository->activeProvider(),
            'keyUrl' => config("llm.providers.{$this->provider}.key_url"),
        ]);
    }

    private function loadProviderState(): void
    {
        $credentials = app(LlmCredentialRepository::class)->find($this->provider);

        $this->model = $credentials?->model
            ?? (string) config("llm.providers.{$this->provider}.default_model", '');
    }

    private function storedCredential(): ?LlmCredential
    {
        return LlmCredential::query()->where('provider', $this->provider)->first();
    }

    /**
     * @return array<int, string>
     */
    private function providerKeys(): array
    {
        return array_keys((array) config('llm.providers', []));
    }

    /**
     * Endpointy Livewire są osiągalne poza stroną, a tu zapisujemy sekret —
     * dlatego uprawnienie sprawdzamy przy każdej akcji, nie tylko przy renderze strony.
     */
    private function authorizeAccess(): void
    {
        $user = auth()->user();
        $permission = app(RoutePermissionService::class)->getPermissionForRoute('system-actions.index');

        abort_unless(
            $user instanceof User && ($permission === null || $user->hasPermission($permission)),
            403,
        );
    }
}
