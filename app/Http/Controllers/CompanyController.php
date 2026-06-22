<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        return view('companies.index');
    }

    public function create(): View
    {
        return view('companies.create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        return redirect()->route('companies.index')
            ->with('success', 'Spółka została dodana.');
    }

    public function show(Company $company, Request $request): View
    {
        $filter = $request->get('filter', 'all');

        $assignmentsQuery = $company->assignments()
            ->with('employee')
            ->orderBy('start_date', 'desc');

        if ($filter === 'active') {
            $assignmentsQuery->active();
        }

        $assignments = $assignmentsQuery->paginate(10)->withQueryString();
        $company->load('comments.user');

        return view('companies.show', compact('company', 'assignments', 'filter'));
    }

    public function edit(Company $company): View
    {
        return view('companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return redirect()->route('companies.index')
            ->with('success', 'Spółka została zaktualizowana.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Spółka została usunięta.');
    }
}
