<?php

namespace App\Http\Controllers;

use App\Enums\OfficeContractType;
use App\Enums\OfficePayMode;
use App\Models\OfficeProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfficeProfileController extends Controller
{
    public function index(): View
    {
        return view('office-profiles.index');
    }

    public function create(): View
    {
        $users = $this->usersWithoutProfile();

        return view('office-profiles.create', [
            'users' => $users,
            'contractTypes' => OfficeContractType::options(),
            'payModes' => OfficePayMode::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProfile($request);

        OfficeProfile::create($validated);

        return redirect()
            ->route('office-profiles.index')
            ->with('success', 'Profil biurowy został utworzony.');
    }

    public function edit(OfficeProfile $officeProfile): View
    {
        $officeProfile->load('user:id,name,email');

        return view('office-profiles.edit', [
            'profile' => $officeProfile,
            'contractTypes' => OfficeContractType::options(),
            'payModes' => OfficePayMode::options(),
        ]);
    }

    public function update(Request $request, OfficeProfile $officeProfile): RedirectResponse
    {
        $validated = $this->validateProfile($request, $officeProfile);

        $officeProfile->update($validated);

        return redirect()
            ->route('office-profiles.index')
            ->with('success', 'Profil biurowy został zaktualizowany.');
    }

    public function destroy(OfficeProfile $officeProfile): RedirectResponse
    {
        $officeProfile->delete();

        return redirect()
            ->route('office-profiles.index')
            ->with('success', 'Profil biurowy został usunięty.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProfile(Request $request, ?OfficeProfile $profile = null): array
    {
        $userRule = Rule::unique('office_profiles', 'user_id');
        if ($profile) {
            $userRule->ignore($profile->id);
        }

        $rules = [
            'contract_type' => ['required', Rule::enum(OfficeContractType::class)],
            'pay_mode' => ['required', Rule::enum(OfficePayMode::class)],
            'fte' => ['required', 'numeric', 'min:0.01', 'max:1'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if (! $profile) {
            $rules['user_id'] = ['required', 'exists:users,id', $userRule];
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['currency'] = strtoupper($validated['currency']);

        if ($profile) {
            unset($validated['user_id']);
        }

        return $validated;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function usersWithoutProfile()
    {
        return User::query()
            ->whereDoesntHave('officeProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
