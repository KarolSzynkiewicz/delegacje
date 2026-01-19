<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Document;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Accommodation;
use App\Models\Location;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Rotation;
use App\Models\VehicleAssignment;
use App\Models\AccommodationAssignment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ViewTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders to set up roles
        // Note: Permissions are now generated dynamically from routes, no PermissionSeeder needed
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        
        $this->user = User::factory()->create();
        
        // Assign administrator role to user for tests
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    /**
     * Test that employee-documents index view renders without SQL errors
     */
    public function test_employee_documents_index_renders(): void
    {
        // Create test data
        $employee = Employee::factory()->create();
        $document = Document::factory()->create();
        EmployeeDocument::factory()->create([
            'employee_id' => $employee->id,
            'document_id' => $document->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employee-documents.index'));

        $response->assertStatus(200);
        $response->assertViewIs('employee-documents.index');
        $response->assertSeeText('Dokumenty pracowników', false);
    }

    /**
     * Test that employees index view renders without SQL errors
     */
    public function test_employees_index_renders(): void
    {
        Employee::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('employees.index'));

        $response->assertStatus(200);
        $response->assertViewIs('employees.index');
    }

    /**
     * Test that employees show view renders without SQL errors
     */
    public function test_employees_show_renders(): void
    {
        $employee = Employee::factory()->create();
        $role = Role::factory()->create();
        $employee->roles()->attach($role);

        $response = $this->actingAs($this->user)
            ->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertViewIs('employees.show');
        $response->assertSeeText($employee->full_name, false);
    }

    /**
     * Test that projects index view renders without SQL errors
     */
    public function test_projects_index_renders(): void
    {
        Project::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertViewIs('projects.index');
    }

    /**
     * Test that projects show view renders without SQL errors
     */
    public function test_projects_show_renders(): void
    {
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.show', $project));

        $response->assertStatus(200);
        $response->assertViewIs('projects.show');
        $response->assertSeeText($project->name, false);
    }

    /**
     * Test that assignments index view renders without SQL errors
     */
    public function test_assignments_index_renders(): void
    {
        $project = Project::factory()->create();
        $employee = Employee::factory()->create();
        $role = Role::factory()->create();
        $employee->roles()->attach($role);
        
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assignments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('assignments.index');
    }

    /**
     * Test that rotations index view renders without SQL errors
     */
    public function test_rotations_index_renders(): void
    {
        $employee = Employee::factory()->create();
        Rotation::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('rotations.index'));

        $response->assertStatus(200);
        $response->assertViewIs('rotations.index');
    }

    /**
     * Test that documents index view renders without SQL errors
     */
    public function test_documents_index_renders(): void
    {
        Document::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('documents.index'));

        $response->assertStatus(200);
        $response->assertViewIs('documents.index');
    }

    /**
     * Test that vehicles index view renders without SQL errors
     */
    public function test_vehicles_index_renders(): void
    {
        Vehicle::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('vehicles.index'));

        $response->assertStatus(200);
        $response->assertViewIs('vehicles.index');
    }

    /**
     * Test that accommodations index view renders without SQL errors
     */
    public function test_accommodations_index_renders(): void
    {
        Accommodation::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('accommodations.index'));

        $response->assertStatus(200);
        $response->assertViewIs('accommodations.index');
    }

    /**
     * Test that vehicle-assignments index view renders without SQL errors
     */
    public function test_vehicle_assignments_index_renders(): void
    {
        $employee = Employee::factory()->create();
        $vehicle = Vehicle::factory()->create();
        VehicleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('vehicle-assignments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('vehicle-assignments.index');
    }

    /**
     * Test that accommodation-assignments index view renders without SQL errors
     */
    public function test_accommodation_assignments_index_renders(): void
    {
        $employee = Employee::factory()->create();
        $accommodation = Accommodation::factory()->create();
        AccommodationAssignment::factory()->create([
            'employee_id' => $employee->id,
            'accommodation_id' => $accommodation->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accommodation-assignments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('accommodation-assignments.index');
    }

    /**
     * Test that weekly-overview index view renders without SQL errors
     */
    public function test_weekly_overview_index_renders(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('weekly-overview.index'));

        $response->assertStatus(200);
        $response->assertViewIs('weekly-overview.index');
    }

    /**
     * Test that dashboard renders without SQL errors
     */
    public function test_dashboard_renders(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }
}
