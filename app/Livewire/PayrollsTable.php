<?php

namespace App\Livewire;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\EmployeeRate;
use App\Models\Adjustment;
use App\Models\Company;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PayrollsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $companyFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'period_start';
    public $sortDirection = 'desc';
    public $bulkMode = false;
    public array $selectedPayrollIds = [];
    public bool $selectAllOnPage = false;
    public array $currentPagePayrollIds = [];
    public bool $showBulkWizard = false;

    public $bulkAmount = '';
    public $bulkDate = '';
    public $bulkCurrency = 'PLN';
    public $bulkDescription = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'companyFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'period_start'],
        'sortDirection' => ['except' => 'desc'],
        'bulkMode' => ['except' => false],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingBulkMode()
    {
        $this->resetSelection();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->companyFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->sortField = 'period_start';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function resetSelection(): void
    {
        $this->selectedPayrollIds = [];
        $this->selectAllOnPage = false;
        $this->showBulkWizard = false;
        $this->bulkAmount = '';
        $this->bulkDate = '';
        $this->bulkCurrency = 'PLN';
        $this->bulkDescription = '';
    }

    public function toggleBulkMode(): void
    {
        $this->bulkMode = ! (bool) $this->bulkMode;
        $this->resetSelection();
    }

    public function updatedSelectAllOnPage($value): void
    {
        if (! $this->bulkMode) {
            $this->selectAllOnPage = false;
            return;
        }

        if ($value) {
            $this->selectedPayrollIds = array_values(array_unique(array_merge(
                array_map('strval', $this->selectedPayrollIds),
                array_map('strval', $this->currentPagePayrollIds)
            )));
        } else {
            $pageIdSet = array_flip(array_map('strval', $this->currentPagePayrollIds));
            $this->selectedPayrollIds = array_values(array_filter(
                array_map('strval', $this->selectedPayrollIds),
                fn (string $id) => ! isset($pageIdSet[$id])
            ));
        }
    }

    public function updatedSelectedPayrollIds(): void
    {
        if (! $this->bulkMode || $this->currentPagePayrollIds === []) {
            return;
        }

        $pageIds = array_map('strval', $this->currentPagePayrollIds);
        $selected = array_map('strval', $this->selectedPayrollIds);

        $this->selectAllOnPage = count($pageIds) > 0
            && count(array_diff($pageIds, $selected)) === 0;
    }

    public function openBulkWizard(): void
    {
        if (! $this->bulkMode) {
            return;
        }

        if (count($this->selectedPayrollIds) < 1) {
            $this->addError('selectedPayrollIds', 'Wybierz przynajmniej jeden payroll.');
            return;
        }

        $this->resetErrorBag();
        $this->showBulkWizard = true;
        if (empty($this->bulkDate)) {
            $this->bulkDate = now()->toDateString();
        }
    }

    public function createBulkAdjustments(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'selectedPayrollIds' => ['required', 'array', 'min:1'],
            'bulkAmount' => ['required', 'numeric'],
            'bulkDate' => ['required', 'date'],
            'bulkCurrency' => ['required', 'string', 'size:3'],
            'bulkDescription' => ['nullable', 'string', 'max:500'],
        ], [], [
            'bulkAmount' => 'kwota',
            'bulkDate' => 'data',
            'bulkCurrency' => 'waluta',
            'bulkDescription' => 'opis',
        ]);

        $payrolls = Payroll::query()
            ->with('employee')
            ->whereIn('id', $this->selectedPayrollIds)
            ->get();

        if ($payrolls->isEmpty()) {
            $this->addError('selectedPayrollIds', 'Nie znaleziono wybranych payrolli.');
            return;
        }

        $amount = round((float) $this->bulkAmount, 2);
        $date = Carbon::parse($this->bulkDate)->toDateString();
        $currency = strtoupper((string) $this->bulkCurrency);
        $description = trim((string) $this->bulkDescription);

        foreach ($payrolls as $payroll) {
            Adjustment::create([
                'employee_id' => $payroll->employee_id,
                'payroll_id' => $payroll->id,
                'amount' => abs($amount),
                'currency' => $currency,
                'type' => 'penalty',
                'date' => $date,
                'notes' => $description !== '' ? $description : 'Rozliczenie zbiorowe',
            ]);

            if ($payroll->canBeRecalculated()) {
                $service = app(\App\Services\GeneratePayrollForEmployee::class);
                $payroll->adjustments_amount = $service->calculateAdjustmentsAmountForPayroll($payroll);
                $payroll->recalculateTotal();
                $payroll->save();
            }
        }

        session()->flash('success', 'Dodano koszt do wybranych payrolli.');
        $this->resetSelection();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function render()
    {
        $query = Payroll::with(['employee', 'adjustments', 'advances']);

        // Wyszukiwanie po pracowniku
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->whereHas('employee', function (Builder $q) use ($searchTerm) {
                $q->where(function ($query) use ($searchTerm) {
                    $query->where('first_name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%']);
                });
            });
        }

        // Filtrowanie po statusie
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Filtrowanie po spółce (przypisanie pracownika nakładające się na okres payrolla)
        if (!empty($this->companyFilter)) {
            $query->whereHas('employee.companyAssignments', function (Builder $q) {
                $q->where('company_id', $this->companyFilter)
                  ->whereColumn('company_assignments.start_date', '<=', 'payrolls.period_end')
                  ->where(function (Builder $q2) {
                      $q2->whereNull('company_assignments.end_date')
                         ->orWhereColumn('company_assignments.end_date', '>=', 'payrolls.period_start');
                  });
            });
        }

        // Filtrowanie po datach (payroll okres nachodzi na zakres)
        if (!empty($this->dateFrom)) {
            $query->whereDate('period_end', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('period_start', '<=', $this->dateTo);
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $payrolls */
        $payrolls = $query->paginate(100);
        $this->currentPagePayrollIds = collect($payrolls->items())->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Rate summary (memoized) for the current page
        $rateMemo = [];
        $collection = collect($payrolls->items())->map(function (Payroll $payroll) use (&$rateMemo) {
            $key = implode('|', [
                $payroll->employee_id,
                (string) $payroll->period_start,
                (string) $payroll->period_end,
                (string) $payroll->currency,
            ]);

            if (!array_key_exists($key, $rateMemo)) {
                $periodStart = Carbon::parse($payroll->period_start)->toDateString();
                $periodEnd = Carbon::parse($payroll->period_end)->toDateString();

                $rates = EmployeeRate::query()
                    ->where('employee_id', $payroll->employee_id)
                    ->where('currency', $payroll->currency)
                    ->where('status', 'active')
                    ->where('start_date', '<=', $periodEnd)
                    ->where(function (Builder $q) use ($periodStart) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $periodStart);
                    })
                    ->pluck('amount')
                    ->map(fn ($a) => (float) $a)
                    ->unique()
                    ->values();

                if ($rates->count() === 1) {
                    $rateMemo[$key] = [
                        'type' => 'single',
                        'amount' => (float) $rates->first(),
                    ];
                } elseif ($rates->count() > 1) {
                    $rateMemo[$key] = [
                        'type' => 'multiple',
                        'amount' => null,
                    ];
                } else {
                    $rateMemo[$key] = [
                        'type' => 'none',
                        'amount' => null,
                    ];
                }
            }

            $payroll->rate_summary = $rateMemo[$key];
            $payroll->correction_totals_by_currency = $payroll->correctionTotalsByCurrency();
            $payroll->payout_totals_by_currency = $payroll->payoutTotalsByCurrency();

            return $payroll;
        });

        $payrolls->setCollection($collection);

        return view('livewire.payrolls-table', [
            'payrolls' => $payrolls,
            'companies' => Company::orderBy('name')->get(),
        ]);
    }
}
