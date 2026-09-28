<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectHourlyRateRequest;
use App\Models\Project;
use App\Services\ProjectHourlyRateService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class ProjectHourlyRateController extends Controller
{
    public function __construct(
        protected ProjectHourlyRateService $hourlyRates
    ) {}

    public function update(StoreProjectHourlyRateRequest $request, Project $project): RedirectResponse
    {
        $this->hourlyRates->addRateFrom(
            $project,
            Carbon::parse($request->validated('start_date')),
            (float) $request->validated('amount')
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Nowa stawka obowiązuje od '.Carbon::parse($request->validated('start_date'))->format('d.m.Y').'. Wcześniejsze godziny zostają przy poprzedniej stawce.');
    }
}
