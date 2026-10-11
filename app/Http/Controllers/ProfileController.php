<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ImageService;
use App\Support\ProfileDevices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->load(['roles', 'managedProjects']);

        return view('profile.edit', [
            'user' => $user,
            'sessions' => ProfileDevices::sessions($user),
            'pushDevices' => ProfileDevices::pushDevices($user),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imageService = app(ImageService::class);
            $validated['image_path'] = $imageService->handleImageUpload(
                $request->file('image'),
                'users',
                $user->image_path
            );
        }

        // Remove image from validated data (it's not a database column)
        unset($validated['image']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function destroySession(Request $request, string $session): RedirectResponse
    {
        $this->deleteSessions($request, $session);

        return Redirect::route('profile.edit');
    }

    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        $this->deleteSessions($request);

        return Redirect::route('profile.edit');
    }

    public function destroyDevice(Request $request, int $device): RedirectResponse
    {
        $request->user()->pushSubscriptions()->whereKey($device)->delete();

        return Redirect::route('profile.edit');
    }

    private function deleteSessions(Request $request, ?string $only = null): void
    {
        $current = hash('sha256', (string) $request->session()->getId());
        $query = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $current);

        if ($only !== null) {
            $query->where('id', $only);
        }

        $query->delete();
    }
}
