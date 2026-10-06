<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Document;
use App\Services\DocumentCompanyMerge;
use App\Services\DocumentTypeCascadeDelete;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentSystemActionController extends Controller
{
    public function mergeForm(): View
    {
        return view('system-actions.merge-documents', [
            'documents' => Document::query()->withCount('employeeDocuments')->orderBy('name')->get(),
            'companies' => Company::query()->orderBy('name')->get(),
        ]);
    }

    public function mergePlan(Request $request, DocumentCompanyMerge $merge): JsonResponse
    {
        $attributes = app(DocumentController::class)->validatedDocument($request);

        $validated = $request->validate([
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['integer', 'distinct', 'exists:documents,id'],
            'company' => ['required', 'array'],
            'company.*' => ['nullable', 'integer', 'exists:companies,id'],
        ], [
            'sources.required' => 'Zaznacz co najmniej jeden typ źródłowy.',
            'sources.min' => 'Zaznacz co najmniej jeden typ źródłowy.',
        ]);

        $sources = collect($validated['sources'])->map(fn ($id) => (int) $id)->unique()->values();
        $map = [];

        foreach ($sources as $id) {
            $companyId = $validated['company'][$id] ?? $validated['company'][(string) $id] ?? null;

            if (! $companyId) {
                throw ValidationException::withMessages([
                    'company' => 'Wybierz spółkę przy każdym zaznaczonym typie.',
                ]);
            }

            $map[$id] = (int) $companyId;
        }

        $target = Document::create($attributes);

        return response()->json($merge->start($target->id, $map));
    }

    public function mergeChunk(Request $request, DocumentCompanyMerge $merge): JsonResponse
    {
        $token = $request->validate([
            'token' => ['required', 'uuid'],
        ])['token'];

        $status = $merge->advance($token);

        if ($status === null) {
            throw ValidationException::withMessages([
                'token' => 'Ta operacja wygasła. Uruchom łączenie od nowa.',
            ]);
        }

        return response()->json($status);
    }

    public function destroyForm(): View
    {
        return view('system-actions.delete-document', [
            'documents' => Document::query()->withCount('employeeDocuments')->orderBy('name')->get(),
        ]);
    }

    public function destroy(Request $request, DocumentTypeCascadeDelete $delete): RedirectResponse
    {
        $validated = $request->validate([
            'document_id' => ['required', 'integer', 'exists:documents,id'],
        ], [
            'document_id.required' => 'Wybierz typ do usunięcia.',
        ]);

        $document = Document::query()->findOrFail($validated['document_id']);
        $result = $delete->delete($document);

        return redirect()
            ->route('system-actions.documents.destroy')
            ->with('success', 'Usunięto „'.$result['name'].'”, wpisów: '.$result['entries'].', plików: '.$result['files'].'.');
    }
}
