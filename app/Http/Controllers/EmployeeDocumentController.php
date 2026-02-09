<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Document;
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
    public function store(Request $request): RedirectResponse
    {
        // #region agent log
        $logFile = storage_path('logs/debug.log');
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logFile, json_encode([
            'id' => 'log_' . time() . '_controller_entry',
            'timestamp' => time() * 1000,
            'location' => 'EmployeeDocumentController.php:45',
            'message' => 'Store method called - request reached controller',
            'data' => [
                'has_file' => $request->hasFile('file'),
                'employee_id' => $request->input('employee_id'),
                'document_id' => $request->input('document_id'),
                'user_id' => auth()->id(),
            ],
            'runId' => 'run1',
            'hypothesisId' => 'A'
            ]) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion

        try {
            // #region agent log
            @file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_validation_start',
                'timestamp' => time() * 1000,
                'location' => 'EmployeeDocumentController.php:47',
                'message' => 'Starting validation',
                'data' => [
                    'request_data' => $request->except(['_token', 'file']),
                ],
                'runId' => 'run1',
                'hypothesisId' => 'E'
            ]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion

            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'document_id' => 'required|exists:documents,id',
                'valid_from' => 'required|date',
                'valid_to' => 'nullable|date|after_or_equal:valid_from',
                'is_okresowy' => 'nullable|boolean',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,odt,txt|max:10240', // 10MB max
            ]);

            // #region agent log
            @file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_validation_passed',
                'timestamp' => time() * 1000,
                'location' => 'EmployeeDocumentController.php:57',
                'message' => 'Validation passed',
                'data' => ['validated_keys' => array_keys($validated)],
                'runId' => 'run1',
                'hypothesisId' => 'E'
            ]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion

            $employee = Employee::findOrFail($validated['employee_id']);
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

            // Upload pliku jeśli został przesłany
            if ($request->hasFile('file')) {
                // #region agent log
                $storageRoot = Storage::disk('public')->path('');
                $storageConfigRoot = config('filesystems.disks.public.root');
                $dataExists = is_dir('/data');
                $dataWritable = is_writable('/data');
                file_put_contents($logFile, json_encode([
                    'id' => 'log_' . time() . '_file_upload_start',
                    'timestamp' => time() * 1000,
                    'location' => 'EmployeeDocumentController.php:73',
                    'message' => 'File upload starting - storage check',
                    'data' => [
                        'storage_root' => $storageRoot,
                        'config_root' => $storageConfigRoot,
                        '/data exists' => $dataExists,
                        '/data writable' => $dataWritable,
                        'file_name' => $request->file('file')->getClientOriginalName(),
                        'file_size' => $request->file('file')->getSize(),
                    ],
                    'runId' => 'run1',
                    'hypothesisId' => 'B,C'
                ]) . "\n", FILE_APPEND);
                // #endregion

                $file = $request->file('file');
                $directory = 'employee_documents/' . $employee->id;
                
                // #region agent log
                $fullDirectoryPath = Storage::disk('public')->path($directory);
                $directoryExists = Storage::disk('public')->exists($directory);
                file_put_contents($logFile, json_encode([
                    'id' => 'log_' . time() . '_directory_check',
                    'timestamp' => time() * 1000,
                    'location' => 'EmployeeDocumentController.php:79',
                    'message' => 'Directory check before upload',
                    'data' => [
                        'directory' => $directory,
                        'full_path' => $fullDirectoryPath,
                        'exists' => $directoryExists,
                        'parent_writable' => is_writable(dirname($fullDirectoryPath)),
                    ],
                    'runId' => 'run1',
                    'hypothesisId' => 'B'
                ]) . "\n", FILE_APPEND);
                // #endregion
                
                // Użyj store() - tak samo jak w ProjectFileController (działa na Railway)
                // Automatycznie generuje unikalną nazwę pliku i tworzy katalogi
                $filePath = $file->store($directory, 'public');
                
                // #region agent log
                file_put_contents($logFile, json_encode([
                    'id' => 'log_' . time() . '_store_result',
                    'timestamp' => time() * 1000,
                    'location' => 'EmployeeDocumentController.php:80',
                    'message' => 'store() result',
                    'data' => [
                        'file_path' => $filePath,
                        'file_path_is_false' => ($filePath === false),
                        'directory' => $directory,
                    ],
                    'runId' => 'run1',
                    'hypothesisId' => 'B'
                ]) . "\n", FILE_APPEND);
                // #endregion
                
                if (!$filePath) {
                    // #region agent log
                    file_put_contents($logFile, json_encode([
                        'id' => 'log_' . time() . '_store_failed',
                        'timestamp' => time() * 1000,
                        'location' => 'EmployeeDocumentController.php:82',
                        'message' => 'store() returned false - upload failed',
                        'data' => [
                            'directory' => $directory,
                            'storage_root' => $storageRoot,
                        ],
                        'runId' => 'run1',
                        'hypothesisId' => 'B'
                    ]) . "\n", FILE_APPEND);
                    // #endregion
                    throw new \Exception('Nie udało się zapisać pliku.');
                }
                
                // #region agent log
                $fileExists = Storage::disk('public')->exists($filePath);
                $fullFilePath = Storage::disk('public')->path($filePath);
                file_put_contents($logFile, json_encode([
                    'id' => 'log_' . time() . '_file_after_upload',
                    'timestamp' => time() * 1000,
                    'location' => 'EmployeeDocumentController.php:85',
                    'message' => 'File check after upload',
                    'data' => [
                        'file_path' => $filePath,
                        'full_path' => $fullFilePath,
                        'exists' => $fileExists,
                        'file_size' => $fileExists ? Storage::disk('public')->size($filePath) : null,
                    ],
                    'runId' => 'run1',
                    'hypothesisId' => 'B'
                ]) . "\n", FILE_APPEND);
                // #endregion
                
                $validated['file_path'] = $filePath;
            }

            // #region agent log
            @file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_creating_model',
                'timestamp' => time() * 1000,
                'location' => 'EmployeeDocumentController.php:88',
                'message' => 'Creating EmployeeDocument model',
                'data' => [
                    'employee_id' => $employee->id,
                    'has_file_path' => isset($validated['file_path']),
                ],
                'runId' => 'run1',
                'hypothesisId' => 'B'
            ]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion

            $employeeDocument = $employee->employeeDocuments()->create($validated);

            // #region agent log
            @file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_success',
                'timestamp' => time() * 1000,
                'location' => 'EmployeeDocumentController.php:90',
                'message' => 'EmployeeDocument created successfully',
                'data' => [
                    'document_id' => $employeeDocument->id,
                    'file_path' => $employeeDocument->file_path,
                ],
                'runId' => 'run1',
                'hypothesisId' => 'B'
            ]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion

            return redirect()->route('employees.show', $employee)
                ->with('success', 'Dokument został dodany.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // #region agent log
            $logFile = '/home/karol/delegacje/.cursor/debug.log';
            file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_validation_error',
                'timestamp' => time() * 1000,
                'location' => 'EmployeeDocumentController.php:92',
                'message' => 'Validation exception caught',
                'data' => [
                    'errors' => $e->errors(),
                ],
                'runId' => 'run1',
                'hypothesisId' => 'E'
            ]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            // ValidationException automatycznie przekierowuje z błędami, ale możemy to obsłużyć jawnie
            return redirect()
                ->route('employee-documents.create', [
                    'employee_id' => $request->input('employee_id'),
                    'document_id' => $request->input('document_id'),
                ])
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // #region agent log
            $logFile = '/home/karol/delegacje/.cursor/debug.log';
            file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_exception',
                'timestamp' => time() * 1000,
                'location' => 'EmployeeDocumentController.php:101',
                'message' => 'Exception caught',
                'data' => [
                    'message' => $e->getMessage(),
                    'trace' => substr($e->getTraceAsString(), 0, 500),
                ],
                'runId' => 'run1',
                'hypothesisId' => 'B,C'
            ]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            return redirect()
                ->route('employee-documents.create', [
                    'employee_id' => $request->input('employee_id'),
                    'document_id' => $request->input('document_id'),
                ])
                ->with('error', 'Wystąpił błąd podczas dodawania dokumentu: ' . $e->getMessage())
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
