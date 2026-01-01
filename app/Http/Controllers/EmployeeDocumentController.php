<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    /**
     * Display a listing of all documents.
     */
    public function index(): View
    {
        return view('employee-documents.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Employee $employee, Request $request): View
    {
        $documents = Document::orderBy('name')->get();
        $selectedDocumentId = $request->query('document_id');
        return view('employee-documents.create', compact('employee', 'documents', 'selectedDocumentId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_okresowy' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,odt,txt|max:10240', // 10MB max
        ]);

        // Ustaw kind na podstawie checkboxa
        $validated['kind'] = $request->has('is_okresowy') && $request->boolean('is_okresowy') ? 'okresowy' : 'bezokresowy';
        unset($validated['is_okresowy']);

        // Jeśli dokument jest bezokresowy, ustaw valid_to na null
        if ($validated['kind'] === 'bezokresowy') {
            $validated['valid_to'] = null;
        }

        // Upload pliku jeśli został przesłany
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = 'employee_documents/' . $employee->id . '/' . time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('public', $fileName);
            $validated['file_path'] = str_replace('public/', '', $filePath);
        }

        $employee->employeeDocuments()->create($validated);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Dokument został dodany.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee, EmployeeDocument $employeeDocument): View
    {
        // Sprawdź czy dokument należy do pracownika
        if ($employeeDocument->employee_id !== $employee->id) {
            abort(404);
        }

        $employeeDocument->load('document');
        $documents = Document::orderBy('name')->get();
        return view('employee-documents.edit', compact('employee', 'employeeDocument', 'documents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee, EmployeeDocument $employeeDocument): RedirectResponse
    {
        // Sprawdź czy dokument należy do pracownika
        if ($employeeDocument->employee_id !== $employee->id) {
            abort(404);
        }

        $validated = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_okresowy' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,odt,txt|max:10240', // 10MB max
            'remove_file' => 'nullable|boolean',
        ]);

        // Ustaw kind na podstawie checkboxa
        $validated['kind'] = $request->has('is_okresowy') && $request->boolean('is_okresowy') ? 'okresowy' : 'bezokresowy';
        unset($validated['is_okresowy']);

        // Jeśli dokument jest bezokresowy, ustaw valid_to na null
        if ($validated['kind'] === 'bezokresowy') {
            $validated['valid_to'] = null;
        }

        // Usuń plik jeśli zaznaczono checkbox
        if ($request->has('remove_file') && $request->boolean('remove_file') && $employeeDocument->file_path) {
            Storage::disk('public')->delete($employeeDocument->file_path);
            $validated['file_path'] = null;
        }

        // Upload nowego pliku jeśli został przesłany
        if ($request->hasFile('file')) {
            // Usuń stary plik jeśli istnieje
            if ($employeeDocument->file_path) {
                Storage::disk('public')->delete($employeeDocument->file_path);
            }
            
            $file = $request->file('file');
            $fileName = 'employee_documents/' . $employee->id . '/' . time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('public', $fileName);
            $validated['file_path'] = str_replace('public/', '', $filePath);
        }

        unset($validated['remove_file']);
        $employeeDocument->update($validated);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Dokument został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee, EmployeeDocument $employeeDocument): RedirectResponse
    {
        // Sprawdź czy dokument należy do pracownika
        if ($employeeDocument->employee_id !== $employee->id) {
            abort(404);
        }

        // Usuń plik jeśli istnieje
        if ($employeeDocument->file_path) {
            Storage::disk('public')->delete($employeeDocument->file_path);
        }

        $employeeDocument->delete();

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Dokument został usunięty.');
    }
}
