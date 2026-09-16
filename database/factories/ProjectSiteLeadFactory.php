<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectSiteLead>
 */
class ProjectSiteLeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'employee_id' => Employee::factory(),
            'start_date' => now()->startOfDay(),
            'end_date' => null,
            'notes' => null,
        ];
    }
}
