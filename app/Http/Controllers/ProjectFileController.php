<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Http\Requests\StoreProjectFileRequest;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;

class ProjectFileController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectFileRequest $request, Project $project): RedirectResponse
    {
        $file = $request->file('file');
        $directory = "projects/{$project->id}/files";
        $filePath = $file->store($directory, 'public');
        
        ProjectFile::create([
            'project_id' => $project->id,
            'name' => $request->input('name') ?? $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Plik został dodany.');
    }

    /**
     * Download the specified file.
     */
    public function download(Project $project, ProjectFile $file): StreamedResponse
    {
        if ($file->project_id !== $project->id) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($file->file_path, $file->name);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, ProjectFile $file): RedirectResponse
    {
        if ($file->project_id !== $project->id) {
            abort(404);
        }

        $file->delete();

        return redirect()->back()->with('success', 'Plik został usunięty.');
    }
}
