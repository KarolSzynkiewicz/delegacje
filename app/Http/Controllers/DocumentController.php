<?php

namespace App\Http\Controllers;

use App\Enums\DocumentPlannerIcon;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $documents = Document::withCount('employeeDocuments')
            ->orderBy('name')
            ->paginate(20);

        return view('documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('documents.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedDocument($request);

        Document::create($validated);

        return redirect()->route('documents.index')
            ->with('success', 'Dokument został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document): View
    {
        $document->load(['employeeDocuments.employee', 'employeeDocuments.company']);
        $document->loadCount('employeeDocuments');

        return view('documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document): View
    {
        return view('documents.edit', compact('document'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        $validated = $this->validatedDocument($request, $document);

        $document->update($validated);

        return redirect()->route('documents.index')
            ->with('success', 'Dokument został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        // Sprawdź czy dokument jest używany
        if ($document->employeeDocuments()->count() > 0) {
            return redirect()->route('documents.index')
                ->with('error', 'Nie można usunąć dokumentu, który jest przypisany do pracowników.');
        }

        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Dokument został usunięty.');
    }

    protected function validatedDocument(Request $request, ?Document $document = null): array
    {
        $nameRule = Rule::unique('documents', 'name');

        if ($document) {
            $nameRule->ignore($document);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', $nameRule],
            'description' => 'nullable|string',
            'is_periodic' => 'required|in:0,1',
            'is_required' => 'nullable|in:0,1',
            'is_company_scoped' => 'nullable|in:0,1',
            'planner_icon' => ['nullable', 'string', 'max:64', Rule::in(array_merge([''], array_map(fn (DocumentPlannerIcon $icon) => $icon->value, DocumentPlannerIcon::cases())))],
        ], [
            'name.unique' => 'Dokument o tej nazwie już istnieje.',
        ]);

        $validated['is_periodic'] = (bool) $validated['is_periodic'];
        $validated['is_required'] = isset($validated['is_required']) ? (bool) $validated['is_required'] : false;
        $validated['is_company_scoped'] = isset($validated['is_company_scoped']) ? (bool) $validated['is_company_scoped'] : false;
        $validated['planner_icon'] = ($validated['planner_icon'] ?? null) ?: null;

        return $validated;
    }
}
