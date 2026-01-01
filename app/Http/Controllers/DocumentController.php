<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:documents,name',
            'description' => 'nullable|string',
            'is_periodic' => 'required|in:0,1',
        ]);
        
        // Konwertuj string na boolean
        $validated['is_periodic'] = (bool) $validated['is_periodic'];

        Document::create($validated);

        return redirect()->route('documents.index')
            ->with('success', 'Dokument został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document): View
    {
        $document->load(['employeeDocuments.employee']);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:documents,name,' . $document->id,
            'description' => 'nullable|string',
            'is_periodic' => 'required|in:0,1',
        ]);
        
        // Konwertuj string na boolean
        $validated['is_periodic'] = (bool) $validated['is_periodic'];

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
}
