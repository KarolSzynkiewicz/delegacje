<?php

namespace App\Observers;

use App\Models\ProcedureTemplate;
use App\Services\ProcedureTemplateVersionService;

class ProcedureTemplateObserver
{
    public function created(ProcedureTemplate $template): void
    {
        app(ProcedureTemplateVersionService::class)->createInitialVersion(
            $template,
            $template->definition ?: ['nodes' => [], 'edges' => []]
        );
    }
}
