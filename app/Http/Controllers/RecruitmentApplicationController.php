<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Models\Employee;
use App\Models\RecruitmentApplication;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentApplicationController extends Controller
{
    use HandlesImageUpload;

    public function index(): View
    {
        return view('recruitment.index');
    }

    public function show(RecruitmentApplication $recruitmentApplication): View
    {
        $recruitmentApplication->load('employee');

        $roles = Role::orderBy('name')->get();

        return view('recruitment.show', [
            'application' => $recruitmentApplication,
            'roles'       => $roles,
        ]);
    }

    public function updateStatus(Request $request, RecruitmentApplication $recruitmentApplication): RedirectResponse
    {
        $request->validate([
            'status'      => 'required|in:pending,reviewing,accepted,rejected,converted',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $recruitmentApplication->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'Status kandydatury został zaktualizowany.');
    }

    public function convert(Request $request, RecruitmentApplication $recruitmentApplication): RedirectResponse
    {
        if ($recruitmentApplication->status === 'converted') {
            return back()->with('error', 'Ta kandydatura została już zatrudniona.');
        }

        $request->validate([
            'roles'   => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ], [
            'roles.required' => 'Wybierz przynajmniej jedną rolę pracownika.',
            'roles.min'      => 'Wybierz przynajmniej jedną rolę pracownika.',
            'roles.*.exists' => 'Wybrana rola nie istnieje.',
        ]);

        $imagePath = null;
        if ($recruitmentApplication->photo_path) {
            // Przenieś zdjęcie z folderu rekrutacji do folderu pracowników
            $oldPath = $recruitmentApplication->photo_path;
            $filename = basename($oldPath);
            $newPath = 'employees/'.$filename;

            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->copy($oldPath, $newPath);
            }

            $imagePath = $newPath;
        }

        $employee = Employee::create([
            'first_name' => $recruitmentApplication->first_name,
            'last_name'  => $recruitmentApplication->last_name,
            'email'      => $recruitmentApplication->email,
            'phone'      => $recruitmentApplication->phone,
            'notes'      => $recruitmentApplication->cover_letter,
            'image_path' => $imagePath,
        ]);

        $employee->roles()->attach($request->roles);

        $recruitmentApplication->update([
            'status'      => 'converted',
            'employee_id' => $employee->id,
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "Kandydat {$employee->full_name} został zatrudniony i dodany do bazy pracowników.");
    }
}
