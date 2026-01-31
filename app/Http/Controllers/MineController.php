<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TimeLog;
use App\Models\Employee;
use App\Models\ProjectAssignment;
use App\Services\TimeLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class MineController extends Controller
{
    public function __construct(
        protected TimeLogService $timeLogService
    ) {
    }
    /**
     * Display projects managed by the current user.
     */
    public function projects(): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Admin widzi wszystko
        if ($user->isAdmin()) {
            return redirect()->route('projects.index');
        }
        
        // Pobierz projekty którymi zarządza użytkownik
        $projectIds = $user->getManagedProjectIds();
        
        if (empty($projectIds)) {
            // Brak projektów - pokaż pustą listę
            return view('mine.projects', [
                'projectIds' => [],
            ]);
        }
        
        return view('mine.projects', [
            'projectIds' => $projectIds,
        ]);
    }

    /**
     * Display a specific project managed by the current user.
     */
    public function show(Project $project): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Admin widzi wszystko
        if ($user->isAdmin()) {
            return redirect()->route('projects.show', $project);
        }
        
        // Sprawdź czy użytkownik zarządza tym projektem
        if (!$user->managesProject($project->id)) {
            abort(403, 'Nie masz uprawnień do tego projektu.');
        }
        
        $project->load(['location', 'demands']);
        return view('mine.projects.show', compact('project'));
    }

    /**
     * Display time logs from projects managed by the current user.
     */
    public function timeLogs(): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Admin widzi wszystko
        if ($user->isAdmin()) {
            return redirect()->route('time-logs.index');
        }
        
        // Pobierz ID projektów którymi zarządza użytkownik
        $projectIds = $user->getManagedProjectIds();
        
        if (empty($projectIds)) {
            return view('mine.time-logs', [
                'projectIds' => [],
            ]);
        }
        
        // Pobierz ID przypisań do tych projektów
        $assignmentIds = ProjectAssignment::whereIn('project_id', $projectIds)
            ->pluck('id')
            ->toArray();
        
        return view('mine.time-logs', [
            'assignmentIds' => $assignmentIds,
        ]);
    }

    /**
     * Display employees from projects managed by the current user.
     */
    public function employees(): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Admin widzi wszystko
        if ($user->isAdmin()) {
            return redirect()->route('employees.index');
        }
        
        // Pobierz ID projektów którym zarządza użytkownik
        $projectIds = $user->getManagedProjectIds();
        
        if (empty($projectIds)) {
            return view('mine.employees', [
                'projectIds' => [],
                'employeeIds' => [],
            ]);
        }
        
        // Pobierz ID pracowników przypisanych do tych projektów
        $employeeIds = ProjectAssignment::whereIn('project_id', $projectIds)
            ->distinct()
            ->pluck('employee_id')
            ->toArray();
        
        return view('mine.employees', [
            'employeeIds' => $employeeIds,
            'projectIds' => $projectIds,
        ]);
    }

    /**
     * Display assignments from projects managed by the current user.
     */
    public function assignments(): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Admin widzi wszystko
        if ($user->isAdmin()) {
            return redirect()->route('project-assignments.index');
        }
        
        // Pobierz ID projektów którym zarządza użytkownik
        $projectIds = $user->getManagedProjectIds();
        
        if (empty($projectIds)) {
            return view('mine.assignments', [
                'projectIds' => [],
            ]);
        }
        
        return view('mine.assignments', [
            'projectIds' => $projectIds,
        ]);
    }

    /**
     * Display employee evaluations for employees from projects managed by the current user.
     */
    public function employeeEvaluations(): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Admin widzi wszystko
        if ($user->isAdmin()) {
            return redirect()->route('employee-evaluations.index');
        }
        
        // Pobierz ID projektów którym zarządza użytkownik
        $projectIds = $user->getManagedProjectIds();
        
        if (empty($projectIds)) {
            return view('mine.employee-evaluations', [
                'employeeIds' => [],
                'employees' => collect(),
            ]);
        }
        
        // Pobierz ID pracowników przypisanych do tych projektów
        $employeeIds = ProjectAssignment::whereIn('project_id', $projectIds)
            ->distinct()
            ->pluck('employee_id')
            ->toArray();
        
        // Pobierz pracowników przypisanych do projektów kierownika
        $employees = Employee::whereIn('id', $employeeIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        
        return view('mine.employee-evaluations', [
            'employeeIds' => $employeeIds,
            'employees' => $employees,
        ]);
    }

    /**
     * Display tasks assigned to the current user from projects managed by the user.
     */
    public function tasks(): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Admin widzi wszystko
        if ($user->isAdmin()) {
            return redirect()->route('projects.index');
        }
        
        // Pobierz ID projektów którymi zarządza użytkownik
        $projectIds = $user->getManagedProjectIds();
        
        return view('mine.tasks', [
            'projectIds' => $projectIds,
        ]);
    }

    /**
     * Display monthly grid for time logs from projects managed by the current user.
     */
    public function monthlyGrid(Request $request): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Admin widzi wszystko
        if ($user->isAdmin()) {
            return redirect()->route('time-logs.monthly-grid', $request->query());
        }
        
        // Pobierz ID projektów którym zarządza użytkownik
        $projectIds = $user->getManagedProjectIds();
        
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        
        if (empty($projectIds)) {
            // Brak projektów - pokaż pusty widok
            $currentDate = Carbon::parse($month . '-01');
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();
            $daysInMonth = $monthStart->daysInMonth;
            $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
            $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');
            
            // Generate empty days array
            $days = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $monthStart->copy()->addDays($day - 1)->startOfDay();
                $days[] = [
                    'number' => $day,
                    'date' => $date,
                    'isWeekend' => $date->isWeekend(),
                ];
            }
            
            return view('time-logs.monthly-grid', [
                'projectsData' => [],
                'days' => $days,
                'currentDate' => $currentDate,
                'prevMonth' => $prevMonth,
                'nextMonth' => $nextMonth,
                'monthStart' => $monthStart,
                'monthEnd' => $monthEnd,
                'isMineRoute' => true,
            ]);
        }
        
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $data = $this->timeLogService->getMonthlyGridData($month, $projectIds);
        $data['isMineRoute'] = true; // Flag dla widoku, żeby linki prowadziły do /mine/*
        
        return view('time-logs.monthly-grid', $data);
    }
}
