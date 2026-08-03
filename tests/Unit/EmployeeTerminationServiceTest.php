<?php

namespace Tests\Unit;

use App\Enums\EmployeeTerminationReason;
use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Services\EmployeeTerminationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTerminationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): EmployeeTerminationService
    {
        return new EmployeeTerminationService;
    }

    public function test_terminate_sets_fields_and_adds_audit_process_without_touching_history(): void
    {
        $employee = Employee::factory()->create();
        $candidate = RecruitmentCandidate::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '600100200',
            'employee_id' => $employee->id,
        ]);

        // Pre-existing history that must remain untouched.
        $oldLead = RecruitmentLead::create(['candidate_id' => $candidate->id]);
        $oldProcess = RecruitmentProcess::create([
            'lead_id' => $oldLead->id,
            'candidate_id' => $candidate->id,
            'status' => RecruitmentStatus::Zatrudniony,
            'employee_id' => $employee->id,
        ]);

        $this->service()->terminate($employee, EmployeeTerminationReason::Disciplinary, 'Naruszenie regulaminu');

        $employee->refresh();
        $this->assertNotNull($employee->terminated_at);
        $this->assertSame(EmployeeTerminationReason::Disciplinary, $employee->termination_reason);
        $this->assertSame('Naruszenie regulaminu', $employee->termination_note);
        $this->assertTrue($employee->isTerminated());

        // Old process untouched.
        $this->assertSame(RecruitmentStatus::Zatrudniony, $oldProcess->fresh()->status);

        // New audit process added, linked to the same candidate + employee.
        $this->assertSame(2, $candidate->processes()->count());
        $this->assertTrue(
            $candidate->processes()
                ->where('status', RecruitmentStatus::BylyPracownik)
                ->where('employee_id', $employee->id)
                ->exists()
        );

        $this->assertTrue($candidate->fresh()->isFormerEmployee());
        $this->assertFalse($candidate->fresh()->isHired());
    }

    public function test_terminate_without_linked_candidate_only_updates_employee(): void
    {
        $employee = Employee::factory()->create();

        $this->service()->terminate($employee, EmployeeTerminationReason::ContractExpired);

        $employee->refresh();
        $this->assertTrue($employee->isTerminated());
        $this->assertSame(EmployeeTerminationReason::ContractExpired, $employee->termination_reason);
        $this->assertSame(0, RecruitmentProcess::count());
    }

    public function test_reinstate_clears_termination_fields_without_touching_processes(): void
    {
        $employee = Employee::factory()->create();
        $candidate = RecruitmentCandidate::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '600100200',
            'employee_id' => $employee->id,
        ]);

        $this->service()->terminate($employee, EmployeeTerminationReason::Other);
        $processCountAfterTerminate = RecruitmentProcess::count();

        $this->service()->reinstate($employee->fresh());

        $employee->refresh();
        $this->assertFalse($employee->isTerminated());
        $this->assertNull($employee->termination_reason);
        $this->assertNull($employee->termination_note);

        // Reinstating does not rewrite or remove the audit trail.
        $this->assertSame($processCountAfterTerminate, RecruitmentProcess::count());
        $this->assertTrue($candidate->fresh()->isHired());
    }
}
