<?php

namespace App\Http\Controllers;

use App\Enums\RecruitmentRejectionReason;
use App\Enums\RecruitmentStatus;
use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Models\Employee;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentProcessController extends Controller
{
    use HandlesImageUpload;

    public function index(): View
    {
        return view('recruitment.index');
    }

    public function show(RecruitmentProcess $recruitmentProcess): View
    {
        $recruitmentProcess->load(['candidate.consents', 'candidate.roles', 'lead', 'employee', 'contactAttempts.user', 'statusHistory.changedBy']);

        $roles = Role::orderBy('name')->get();

        return view('recruitment.show', [
            'application' => $recruitmentProcess,
            'roles' => $roles,
        ]);
    }

    public function updateStatus(Request $request, RecruitmentProcess $recruitmentProcess): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:'.implode(',', array_column(RecruitmentStatus::cases(), 'value')),
            'admin_notes' => 'nullable|string|max:2000',
            'rejection_reason' => ['nullable', 'in:'.implode(',', array_column(RecruitmentRejectionReason::cases(), 'value'))],
        ]);

        $recruitmentProcess->update(['admin_notes' => $request->admin_notes]);

        $recruitmentProcess->transitionTo(
            RecruitmentStatus::from($request->string('status')->value()),
            auth()->id(),
            $request->filled('rejection_reason') ? RecruitmentRejectionReason::from($request->string('rejection_reason')->value()) : null
        );

        return back()->with('success', 'Status kandydatury został zaktualizowany.');
    }

    public function convert(Request $request, RecruitmentProcess $recruitmentProcess): RedirectResponse
    {
        if ($recruitmentProcess->employee_id) {
            return back()->with('error', 'Ta kandydatura została już zatrudniona.');
        }

        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ], [
            'roles.required' => 'Wybierz przynajmniej jedną rolę pracownika.',
            'roles.min' => 'Wybierz przynajmniej jedną rolę pracownika.',
            'roles.*.exists' => 'Wybrana rola nie istnieje.',
        ]);

        $candidate = $recruitmentProcess->candidate;

        $imagePath = null;
        if ($candidate->photo_path) {
            // Przenieś zdjęcie z folderu rekrutacji do folderu pracowników
            $oldPath = $candidate->photo_path;
            $filename = basename($oldPath);
            $newPath = 'employees/'.$filename;

            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->copy($oldPath, $newPath);
            }

            $imagePath = $newPath;
        }

        $employee = Employee::create([
            'first_name' => $candidate->first_name,
            'last_name' => $candidate->last_name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'notes' => null,
            'image_path' => $imagePath,
        ]);

        $employee->roles()->attach($request->roles);

        $candidate->update(['employee_id' => $employee->id]);

        $recruitmentProcess->transitionTo(RecruitmentStatus::Zatrudniony, auth()->id());
        $recruitmentProcess->update(['employee_id' => $employee->id]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "Kandydat {$employee->full_name} został zatrudniony i dodany do bazy pracowników.");
    }
}
