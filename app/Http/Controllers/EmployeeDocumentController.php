<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Document;
use App\Http\Requests\StoreEmployeeDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function create(Request $request): View
    {
        $employeeId = $request->query('employee_id');
        if (!$employeeId) {
            return redirect()->route('employees.index')
                ->with('error', 'Musisz wybrać pracownika');
        }

        $employee = Employee::findOrFail($employeeId);
        $documents = Document::orderBy('name')->get();
        $selectedDocumentId = $request->query('document_id');
        return view('employee-documents.create', compact('employee', 'documents', 'selectedDocumentId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeDocumentRequest $request): RedirectResponse
    {
        \Log::info('EmployeeDocument::store START', [
            'employee_id' => $request->input('employee_id'),
            'document_id' => $request->input('document_id'),
            'has_file' => $request->hasFile('file'),
            'user_id' => auth()->id(),
            'env' => app()->environment(),
        ]);

        try {
            $employee = Employee::findOrFail($request->input('employee_id'));
            \Log::info('Employee found', ['employee' => $employee->id]);
            
            $validated = $request->validated();
            \Log::info('Validation passed', ['keys' => array_keys($validated)]);
            
            unset($validated['employee_id']);

            // Ustaw kind na podstawie checkboxa
            $validated['kind'] = $request->has('is_okresowy') && $request->boolean('is_okresowy') ? 'okresowy' : 'bezokresowy';
            unset($validated['is_okresowy']);

            // Jeśli dokument jest bezokresowy, ustaw valid_to na null
            if ($validated['kind'] === 'bezokresowy') {
                $validated['valid_to'] = null;
            }

            // Ustaw type - pole wymagane przez bazę danych (może być null jeśli nieużywane)
            $validated['type'] = null;

            \Log::info('Data prepared', ['data' => $validated]);

            // Upload pliku jeśli został przesłany
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $directory = 'employee_documents/' . $employee->id;
                \Log::info('File upload starting', ['directory' => $directory]);
                
                $filePath = $file->store($directory, 'public');
                \Log::info('File uploaded', ['path' => $filePath]);

                $validated['file_path'] = $filePath;
            }

            \Log::info('Creating EmployeeDocument', ['employee_id' => $employee->id]);
            $doc = $employee->employeeDocuments()->create($validated);
            \Log::info('EmployeeDocument created successfully', ['id' => $doc->id]);

            return redirect()->route('employees.show', $employee)
                ->with('success', 'Dokument został dodany.');
        } catch (\Exception $e) {
            \Log::error('EmployeeDocument::store ERROR', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()
                ->route('employee-documents.create', [
                    'employee_id' => $request->input('employee_id'),
                    'document_id' => $request->input('document_id'),
                ])
                ->with('error', 'Błąd: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeDocument $employeeDocument): View
    {
        $employee = $employeeDocument->employee;
        $employeeDocument->load('document');
        $documents = Document::orderBy('name')->get();
        return view('employee-documents.edit', compact('employee', 'employeeDocument', 'documents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeDocument $employeeDocument): RedirectResponse
    {
        try {
            $employee = $employeeDocument->employee;

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
                $directory = 'employee_documents/' . $employee->id;
                
                // Użyj store() - tak samo jak w ProjectFileController (działa na Railway)
                // Automatycznie generuje unikalną nazwę pliku i tworzy katalogi
                $filePath = $file->store($directory, 'public');
                $validated['file_path'] = $filePath;
            }

            unset($validated['remove_file']);
            $employeeDocument->update($validated);

            return redirect()->route('employees.show', $employee)
                ->with('success', 'Dokument został zaktualizowany.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ValidationException automatycznie przekierowuje z błędami, ale możemy to obsłużyć jawnie
            return redirect()
                ->route('employee-documents.edit', $employeeDocument)
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()
                ->route('employee-documents.edit', $employeeDocument)
                ->with('error', 'Wystąpił błąd podczas aktualizacji dokumentu: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Download the specified employee document file.
     * Pliki są w public storage, ale dostęp jest kontrolowany przez autoryzację.
     */
    public function download(EmployeeDocument $employeeDocument): StreamedResponse
    {
        // Sprawdź uprawnienia - użytkownik musi mieć dostęp do dokumentów pracowników
        if (!auth()->user()->hasPermission('employee-documents.view')) {
            abort(403, 'Nie masz uprawnień do pobierania tego dokumentu.');
        }
        
        if (!$employeeDocument->file_path) {
            abort(404, 'Plik nie został znaleziony.');
        }
        
        if (!Storage::disk('public')->exists($employeeDocument->file_path)) {
            abort(404, 'Plik nie istnieje na serwerze.');
        }
        
        $fileName = ($employeeDocument->document->name ?? 'document') . '_' . basename($employeeDocument->file_path);
        
        return Storage::disk('public')->download($employeeDocument->file_path, $fileName);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeDocument $employeeDocument): RedirectResponse
    {
        $employee = $employeeDocument->employee;

        // Usuń plik jeśli istnieje
        if ($employeeDocument->file_path) {
            Storage::disk('public')->delete($employeeDocument->file_path);
        }

        $employeeDocument->delete();

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Dokument został usunięty.');
    }
}
