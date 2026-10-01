<?php

namespace App\Livewire;

use App\Models\Project;
use App\Services\ProjectStaffingDeltaService;
use Carbon\Carbon;
use Livewire\Component;

class ProjectStaffingDelta extends Component
{
    public int $projectId;

    public string $weekStart;

    public string $projectName = '';

    public bool $show = false;

    public function mount(int $projectId, string $weekStart, string $projectName = ''): void
    {
        $this->projectId = $projectId;
        $this->weekStart = $weekStart;
        $this->projectName = $projectName;
    }

    public function openModal(): void
    {
        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
    }

    public function render()
    {
        $delta = null;
        $loaded = false;

        if ($this->show) {
            $project = Project::query()->find($this->projectId);
            if ($project) {
                if ($this->projectName === '') {
                    $this->projectName = $project->name;
                }
                $delta = app(ProjectStaffingDeltaService::class)
                    ->forProjectWeek($project, Carbon::parse($this->weekStart)->startOfDay());
            } else {
                $delta = [
                    'left' => collect(),
                    'arrived' => collect(),
                    'ending' => collect(),
                    'arriving' => collect(),
                    'week_label' => '',
                ];
            }
            $loaded = true;
        }

        return view('livewire.project-staffing-delta', [
            'delta' => $delta,
            'loaded' => $loaded,
        ]);
    }
}
