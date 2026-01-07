<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Document;
use App\Models\EmployeeDocument;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeDocumentsGrouped extends Component
{
    use WithPagination;

    public $searchEmployee = '';
    public $filterStatus = '';
    public $filterDocument = '';
    
    protected $queryString = [
        'searchEmployee' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterDocument' => ['except' => ''],
    ];

    public function updatingSearchEmployee()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterDocument()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->searchEmployee = '';
        $this->filterStatus = '';
        $this->filterDocument = '';
        $this->resetPage();
    }

    public function render()
    {
        // Pobierz pracowników z filtrowaniem
        $employeesQuery = Employee::query();
        
        if ($this->searchEmployee) {
            $employeesQuery->where(function($q) {
                $q->where('first_name', 'like', '%' . $this->searchEmployee . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchEmployee . '%');
            });
        }
        
        // Eager load employeeDocuments with document relationship to avoid N+1 queries
        $employees = $employeesQuery
            ->with(['employeeDocuments.document'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        
        $documents = Document::orderBy('name')->get();
        
        // Filtruj dokumenty jeśli wybrano
        if ($this->filterDocument) {
            $documents = $documents->filter(function($doc) {
                return $doc->id == $this->filterDocument;
            });
        }
        
        // Dla każdego pracownika sprawdź które dokumenty ma
        $groupedData = [];
        
        foreach ($employees as $employee) {
            // Użyj już załadowanych relacji zamiast wykonywać nowe zapytania
            $employeeDocuments = $employee->employeeDocuments;
            
            // Filtruj po dokumencie jeśli wybrano
            if ($this->filterDocument) {
                $employeeDocuments = $employeeDocuments->filter(function($doc) {
                    return $doc->document_id == $this->filterDocument;
                });
            }
            
            $employeeDocuments = $employeeDocuments->keyBy('document_id');
            
            $documentsStatus = [];
            foreach ($documents as $document) {
                $employeeDocument = $employeeDocuments->get($document->id);
                $status = $this->getDocumentStatus($employeeDocument);
                
                // Filtruj po statusie
                if ($this->filterStatus && $this->filterStatus !== 'all') {
                    if ($this->filterStatus === 'brak' && $employeeDocument !== null) {
                        continue;
                    }
                    if ($this->filterStatus === 'has' && $employeeDocument === null) {
                        continue;
                    }
                    if ($this->filterStatus !== 'brak' && $this->filterStatus !== 'has' && $status !== $this->filterStatus) {
                        continue;
                    }
                }
                
                $documentsStatus[] = [
                    'document' => $document,
                    'employeeDocument' => $employeeDocument,
                    'hasDocument' => $employeeDocument !== null,
                    'status' => $status,
                ];
            }
            
            // Pomiń pracownika jeśli nie ma żadnych dokumentów po filtrowaniu
            if (empty($documentsStatus)) {
                continue;
            }
            
            $groupedData[] = [
                'employee' => $employee,
                'documents' => $documentsStatus,
            ];
        }
        
        return view('livewire.employee-documents-grouped', [
            'groupedData' => $groupedData,
            'allDocuments' => Document::orderBy('name')->get(),
        ]);
    }
    
    private function getDocumentStatus($employeeDocument): string
    {
        if (!$employeeDocument) {
            return 'brak';
        }
        
        if ($employeeDocument->kind === 'bezokresowy') {
            return 'ważny';
        }
        
        if ($employeeDocument->isExpired()) {
            return 'wygasł';
        }
        
        if ($employeeDocument->isExpiringSoon()) {
            return 'wygasa_wkrotce';
        }
        
        return 'ważny';
    }
}
