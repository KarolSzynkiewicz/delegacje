<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Attachment $attachment): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $attachment);

        if (! Storage::disk('public')->exists($attachment->file_path)) {
            return redirect()->back()->with('error', 'Plik nie istnieje.');
        }

        $name = $attachment->original_name ?: basename($attachment->file_path);

        return Storage::disk('public')->download($attachment->file_path, $name);
    }

    public function preview(Attachment $attachment): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $attachment);

        if (! $attachment->isPreviewable() || ! Storage::disk('public')->exists($attachment->file_path)) {
            return redirect()->route('attachments.download', $attachment);
        }

        $name = str_replace(['"', "\r", "\n"], '', $attachment->original_name ?: basename($attachment->file_path));

        return Storage::disk('public')->response($attachment->file_path, $name, [
            'Content-Type' => $attachment->mimeType(),
        ]);
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $this->authorize('delete', $attachment);

        $attachment->delete();

        return redirect()->back()->with('success', 'Załącznik został usunięty.');
    }
}
