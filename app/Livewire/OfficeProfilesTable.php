<?php

namespace App\Livewire;

use App\Enums\OfficeContractType;
use App\Enums\OfficePayMode;
use App\Models\OfficeProfile;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Wszyscy użytkownicy systemu + opcjonalny profil biurowy, edytowany inline.
 * Włączenie „Biuro” tworzy profil z domyślnymi wartościami; wyłączenie dezaktywuje
 * (godziny zostają w bazie).
 */
class OfficeProfilesTable extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    /** all | office | none */
    #[Url(except: 'all')]
    public string $scope = 'all';

    /**
     * Stan edycji per user_id.
     *
     * @var array<int, array{
     *     enabled: bool,
     *     contract_type: string,
     *     pay_mode: string,
     *     fte: string,
     *     hourly_rate: string,
     *     currency: string,
     *     started_on: string,
     *     ended_on: string,
     *     is_active: bool
     * }>
     */
    public array $rows = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingScope(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->scope = 'all';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function updatedRows(mixed $value, string $key): void
    {
        $this->ensureCanUpdate();

        [$userId, $field] = array_pad(explode('.', $key, 2), 2, null);
        if (! ctype_digit((string) $userId) || $field === null) {
            return;
        }

        $userId = (int) $userId;
        $user = User::query()->findOrFail($userId);

        if ($field === 'enabled') {
            $this->toggleEnabled($user, (bool) $value);

            return;
        }

        $profile = $user->officeProfile;
        if (! $profile) {
            // Pierwsza edycja pola bez profilu → utwórz z domyślnymi + tą wartością
            $profile = $this->createDefaultProfile($user);
            $this->hydrateRow($user->fresh(['officeProfile']));
        }

        $this->persistField($profile, $field, $value, $userId);
        $this->dispatch('office-profile-saved', userId: $userId, field: $field);
    }

    public function canUpdate(): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->isAdmin()
            || $user->hasPermission('office-profiles.update')
            || $user->hasPermission('office-profiles.create')
        );
    }

    public function render()
    {
        $query = User::query()->with('officeProfile')->orderBy('name');

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            });
        }

        if ($this->scope === 'office') {
            $query->whereHas('officeProfile', fn ($q) => $q->where('is_active', true));
        } elseif ($this->scope === 'none') {
            $query->where(function ($q) {
                $q->whereDoesntHave('officeProfile')
                    ->orWhereHas('officeProfile', fn ($q2) => $q2->where('is_active', false));
            });
        }

        $users = $query->paginate(30);

        foreach ($users as $user) {
            $this->hydrateRow($user);
        }

        return view('livewire.office-profiles-table', [
            'users' => $users,
            'contractTypes' => OfficeContractType::options(),
            'payModes' => OfficePayMode::options(),
            'canUpdate' => $this->canUpdate(),
        ]);
    }

    private function hydrateRow(User $user): void
    {
        $profile = $user->officeProfile;
        $this->rows[$user->id] = [
            'enabled' => $profile !== null && $profile->is_active,
            'contract_type' => $profile?->contract_type?->value ?? OfficeContractType::Uop->value,
            'pay_mode' => $profile?->pay_mode?->value ?? OfficePayMode::Hourly->value,
            'fte' => $profile ? (string) $profile->fte : '1.00',
            'hourly_rate' => $profile && $profile->hourly_rate !== null ? (string) $profile->hourly_rate : '',
            'currency' => $profile?->currency ?? 'PLN',
            'started_on' => $profile?->started_on?->format('Y-m-d') ?? '',
            'ended_on' => $profile?->ended_on?->format('Y-m-d') ?? '',
            'is_active' => $profile?->is_active ?? false,
        ];
    }

    private function toggleEnabled(User $user, bool $enabled): void
    {
        $profile = $user->officeProfile;

        if ($enabled) {
            if (! $profile) {
                $this->createDefaultProfile($user);
            } else {
                $profile->update(['is_active' => true]);
            }
        } elseif ($profile) {
            $profile->update(['is_active' => false]);
        }

        $this->hydrateRow($user->fresh(['officeProfile']));
        $this->dispatch('office-profile-saved', userId: $user->id, field: 'enabled');
    }

    private function createDefaultProfile(User $user): OfficeProfile
    {
        return OfficeProfile::create([
            'user_id' => $user->id,
            'contract_type' => OfficeContractType::Uop,
            'pay_mode' => OfficePayMode::Hourly,
            'fte' => 1,
            'hourly_rate' => null,
            'currency' => 'PLN',
            'started_on' => now()->toDateString(),
            'ended_on' => null,
            'is_active' => true,
            'notes' => null,
        ]);
    }

    private function persistField(OfficeProfile $profile, string $field, mixed $value, int $userId): void
    {
        $attr = "rows.{$userId}.{$field}";

        switch ($field) {
            case 'contract_type':
                $this->validateInline($attr, ['required', Rule::enum(OfficeContractType::class)], 'umowa');
                $profile->update(['contract_type' => $value]);
                break;

            case 'pay_mode':
                $this->validateInline($attr, ['required', Rule::enum(OfficePayMode::class)], 'rozliczenie');
                $profile->update(['pay_mode' => $value]);
                break;

            case 'fte':
                $this->validateInline($attr, ['required', 'numeric', 'min:0.01', 'max:1'], 'Część etatu');
                $profile->update(['fte' => $value]);
                break;

            case 'hourly_rate':
                $this->validateInline($attr, ['nullable', 'numeric', 'min:0'], 'stawka');
                $profile->update([
                    'hourly_rate' => $value === '' || $value === null ? null : $value,
                ]);
                break;

            case 'currency':
                $this->validateInline($attr, ['required', 'string', 'size:3'], 'waluta');
                $profile->update(['currency' => strtoupper((string) $value)]);
                $this->rows[$userId]['currency'] = strtoupper((string) $value);
                break;

            case 'started_on':
                $this->validateInline($attr, ['nullable', 'date'], 'od');
                $profile->update([
                    'started_on' => $value === '' || $value === null ? null : $value,
                ]);
                break;

            case 'ended_on':
                $this->validateInline($attr, ['nullable', 'date'], 'do');
                $profile->update([
                    'ended_on' => $value === '' || $value === null ? null : $value,
                ]);
                break;

            case 'is_active':
                $profile->update(['is_active' => (bool) $value]);
                $this->rows[$userId]['enabled'] = (bool) $value;
                break;

            default:
                return;
        }
    }

    /**
     * @param  list<string|Rule>  $rules
     */
    private function validateInline(string $attribute, array $rules, string $label): void
    {
        $this->resetErrorBag($attribute);
        $this->validateOnly($attribute, [$attribute => $rules], [], [$attribute => $label]);
    }

    private function ensureCanUpdate(): void
    {
        abort_unless($this->canUpdate(), 403);
    }
}
