<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\Employee;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeDocumentsGrouped extends Component
{
    use WithPagination;

    public $searchEmployee = '';

    public $filterStatus = '';

    public $filterDocument = '';

    public $filterRequired = '';

    protected $queryString = [
        'searchEmployee' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterDocument' => ['except' => ''],
        'filterRequired' => ['except' => ''],
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

    public function updatingFilterRequired()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->searchEmployee = '';
        $this->filterStatus = '';
        $this->filterDocument = '';
        $this->filterRequired = '';
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        // Pobierz pracowników z filtrowaniem
        $employeesQuery = Employee::query();

        if ($this->searchEmployee) {
            $employeesQuery->where(function ($q) {
                $q->where('first_name', 'like', '%'.$this->searchEmployee.'%')
                    ->orWhere('last_name', 'like', '%'.$this->searchEmployee.'%');
            });
        }

        // Eager load employeeDocuments with document relationship to avoid N+1 queries
        $employees = $employeesQuery
            ->with(['employeeDocuments.document'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        $documents = Document::orderBy('name')->get();

        // Filtruj dokumenty jeśli wybrano
        if ($this->filterDocument) {
            $documents = $documents->filter(function ($doc) {
                return $doc->id == $this->filterDocument;
            });
        }

        // Dla każdego pracownika sprawdź które dokumenty ma
        $groupedData = [];

        foreach ($employees as $employee) {
            $allEmployeeDocuments = $employee->employeeDocuments;

            $documentsStatus = [];
            foreach ($documents as $document) {
                $statusOrder = ['przyszły' => 0, 'ważny' => 1, 'wygasa_wkrotce' => 2, 'wygasł' => 3, 'brak' => 4];

                $instances = $allEmployeeDocuments
                    ->where('document_id', $document->id)
                    ->sortBy(fn ($ed) => [
                        $statusOrder[$ed->getUiValidityStatus()] ?? 9,
                        optional($ed->valid_from)->timestamp ?? 0,
                    ])
                    ->values();

                // Filtruj po wymaganych
                if ($this->filterRequired !== '') {
                    $isRequired = $document->is_required ?? false;
                    if ($this->filterRequired === 'required' && ! $isRequired) {
                        continue;
                    }
                    if ($this->filterRequired === 'not_required' && $isRequired) {
                        continue;
                    }
                }

                // Czy ten typ dokumentu w ogóle pokazać (filtr statusu)
                if ($this->filterStatus && $this->filterStatus !== 'all') {
                    if ($this->filterStatus === 'brak' && $instances->isNotEmpty()) {
                        continue;
                    }
                    if ($this->filterStatus === 'has' && $instances->isEmpty()) {
                        continue;
                    }
                    if (! in_array($this->filterStatus, ['brak', 'has'], true)) {
                        $hasMatching = $instances->contains(
                            fn ($ed) => $ed->getUiValidityStatus() === $this->filterStatus
                        );
                        if (! $hasMatching) {
                            continue;
                        }
                    }
                }

                // Wiersze do wyświetlenia (przy filtrze ważny/wygasł/wkrótce — tylko pasujące instancje)
                $displayInstances = $instances;
                if ($this->filterStatus && ! in_array($this->filterStatus, ['', 'all', 'brak', 'has'], true)) {
                    $displayInstances = $instances
                        ->filter(fn ($ed) => $ed->getUiValidityStatus() === $this->filterStatus)
                        ->values();
                }

                $requiredCompliance = $this->resolveRequiredComplianceToday($employee, $document);

                if ($instances->isEmpty()) {
                    $documentsStatus[] = [
                        'document' => $document,
                        'hasDocument' => false,
                        'status' => 'brak',
                        'instances' => [],
                        'showAddAnother' => false,
                        'requiredCompliance' => $requiredCompliance,
                    ];

                    continue;
                }

                $instanceItems = $displayInstances->map(fn ($ed) => [
                    'employeeDocument' => $ed,
                    'status' => $ed->getUiValidityStatus(),
                ])->values()->all();

                $documentsStatus[] = [
                    'document' => $document,
                    'hasDocument' => true,
                    'instances' => $instanceItems,
                    'showAddAnother' => true,
                    'requiredCompliance' => $requiredCompliance,
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
            'employees' => $employees,
        ]);
    }

    /**
     * Zgodnie z logiką {@see Employee::hasDocumentTypeActiveInDateRange()} dla dzisiejszej daty.
     */
    private function resolveRequiredComplianceToday(Employee $employee, Document $document): string
    {
        if (! Schema::hasColumn('documents', 'is_required')) {
            return 'not_required';
        }

        if (! ($document->is_required ?? false)) {
            return 'not_required';
        }

        return $employee->hasDocumentTypeActiveInDateRange(
            $document->id,
            now()->startOfDay(),
            now()->endOfDay()
        ) ? 'ok' : 'missing';
    }
}
