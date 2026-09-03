<?php

namespace App\Services;

use App\Models\ProcedureTemplate;
use App\Models\ProcedureTemplateVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcedureTemplateVersionService
{
    /**
     * Create immutable version 1 when a template is first saved.
     *
     * @param  array{nodes: array, edges: array}  $definition
     */
    public function createInitialVersion(ProcedureTemplate $template, array $definition): ProcedureTemplateVersion
    {
        if ($template->versions()->exists()) {
            return $template->versions()->orderByDesc('version_number')->first();
        }

        return ProcedureTemplateVersion::create([
            'procedure_template_id' => $template->id,
            'version_number' => 1,
            'definition' => $definition,
            'changed_by' => Auth::id() ?? $template->created_by,
            'changed_at' => now(),
        ]);
    }

    /**
     * Persist a new immutable version when the working template definition changes.
     *
     * @param  array{nodes: array, edges: array}  $definition
     */
    public function publishDefinition(ProcedureTemplate $template, array $definition): ProcedureTemplateVersion
    {
        $latest = $this->latestVersion($template);

        if ($latest !== null && $this->definitionsEqual($latest->definition, $definition)) {
            return $latest;
        }

        $nextNumber = ($latest?->version_number ?? 0) + 1;

        return ProcedureTemplateVersion::create([
            'procedure_template_id' => $template->id,
            'version_number' => $nextNumber,
            'definition' => $definition,
            'changed_by' => Auth::id() ?? $template->created_by,
            'changed_at' => now(),
        ]);
    }

    public function latestVersion(ProcedureTemplate $template): ?ProcedureTemplateVersion
    {
        return $template->versions()->orderByDesc('version_number')->first();
    }

    /**
     * Version used when starting a new run — always the newest published version.
     */
    public function resolveVersionForRun(ProcedureTemplate $template): ProcedureTemplateVersion
    {
        $latest = $this->latestVersion($template);

        if ($latest !== null) {
            return $latest;
        }

        return $this->createInitialVersion(
            $template,
            $template->definition ?: ['nodes' => [], 'edges' => []]
        );
    }

    /**
     * @return array<int, array{version: ProcedureTemplateVersion, runs_count: int}>
     */
    public function versionsWithRunCounts(ProcedureTemplate $template): array
    {
        $counts = DB::table('procedure_runs')
            ->select('procedure_template_version_id', DB::raw('count(*) as aggregate'))
            ->where('procedure_template_id', $template->id)
            ->groupBy('procedure_template_version_id')
            ->pluck('aggregate', 'procedure_template_version_id');

        return $template->versions()
            ->with('changedBy')
            ->orderByDesc('version_number')
            ->get()
            ->map(fn (ProcedureTemplateVersion $version) => [
                'version' => $version,
                'runs_count' => (int) ($counts[$version->id] ?? 0),
            ])
            ->all();
    }

    public function deleteVersion(ProcedureTemplateVersion $version): void
    {
        if ($version->runs()->exists()) {
            throw new RuntimeException(
                'Nie można usunąć wersji v'.$version->version_number.' — istnieją przebiegi na tej wersji.'
            );
        }

        $version->delete();
    }

    /** @param  array{nodes?: array, edges?: array}|null  $a  @param  array{nodes?: array, edges?: array}|null  $b */
    private function definitionsEqual(?array $a, ?array $b): bool
    {
        return json_encode($a ?? ['nodes' => [], 'edges' => []])
            === json_encode($b ?? ['nodes' => [], 'edges' => []]);
    }
}
