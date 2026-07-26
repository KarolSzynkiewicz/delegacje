<?php

namespace App\Observers;

use App\Models\ProcedureTemplate;
use App\Models\ProcedureTemplateVersion;
use Illuminate\Support\Facades\Auth;

class ProcedureTemplateObserver
{
    public function updated(ProcedureTemplate $template): void
    {
        if (! $template->wasChanged('definition')) {
            return;
        }

        ProcedureTemplateVersion::create([
            'procedure_template_id' => $template->id,
            'definition'            => $template->getOriginal('definition'),
            'changed_by'            => Auth::id() ?? $template->created_by,
            'changed_at'            => now(),
        ]);
    }
}
