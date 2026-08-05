<?php

namespace Tests\Unit;

use App\Enums\EmployeeTerminationReason;
use App\Enums\RecruitmentReferralSource;
use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Services\EmployeeLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): EmployeeLifecycleService
    {
        return new EmployeeLifecycleService;
    }

    public function test_record_hire_outside_process_creates_candidate_lead_and_hired_process(): void
    {
        $role = Role::factory()->create();
        $employee = Employee::factory()->create([
            'phone' => '600100200',
            'email' => 'jan@example.com',
        ]);
        $employee->roles()->sync([$role->id]);

        $this->service()->recordHireOutsideProcess($employee);

        $candidate = RecruitmentCandidate::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($candidate);
        $this->assertSame('48600100200', $candidate->phone);
        $this->assertTrue($candidate->roles->contains('id', $role->id));

        $lead = $candidate->leads()->latest('id')->first();
        $this->assertSame(RecruitmentReferralSource::EmployeeLifecycle, $lead->referral_source);
        $this->assertStringStartsWith('Zatrudnienie poza procesem – ', $lead->referral_source_detail);

        $this->assertTrue(
            $candidate->processes()
                ->where('status', RecruitmentStatus::Zatrudniony)
                ->where('employee_id', $employee->id)
                ->exists()
        );
    }

    public function test_record_hire_outside_process_links_existing_unhired_candidate_by_phone(): void
    {
        $employee = Employee::factory()->create(['phone' => '501999888']);
        $existing = RecruitmentCandidate::create([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'phone' => '501999888',
            'email' => 'anna@example.com',
        ]);

        $this->service()->recordHireOutsideProcess($employee);

        $this->assertSame(1, RecruitmentCandidate::where('phone', '48501999888')->count());
        $this->assertSame($employee->id, $existing->fresh()->employee_id);
        $this->assertSame('anna@example.com', $existing->fresh()->email);
        $this->assertStringStartsWith(
            'Zatrudnienie poza procesem – ',
            $existing->leads()->latest('id')->first()->referral_source_detail
        );
    }

    public function test_record_hire_outside_process_is_idempotent(): void
    {
        $employee = Employee::factory()->create(['phone' => '600100200']);

        $this->service()->recordHireOutsideProcess($employee);
        $this->service()->recordHireOutsideProcess($employee);

        $this->assertSame(1, RecruitmentCandidate::where('employee_id', $employee->id)->count());
        $this->assertSame(1, RecruitmentProcess::where('employee_id', $employee->id)->count());
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

        $this->assertSame(RecruitmentStatus::Zatrudniony, $oldProcess->fresh()->status);

        $this->assertSame(2, $candidate->processes()->count());
        $this->assertTrue(
            $candidate->processes()
                ->where('status', RecruitmentStatus::BylyPracownik)
                ->where('employee_id', $employee->id)
                ->exists()
        );

        $terminationLead = $candidate->leads()->latest('id')->first();
        $this->assertSame(RecruitmentReferralSource::EmployeeLifecycle, $terminationLead->referral_source);
        $this->assertStringStartsWith('Zwolnienie pracownika – ', $terminationLead->referral_source_detail);

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

    public function test_reinstate_clears_termination_fields_and_adds_audit_process(): void
    {
        $employee = Employee::factory()->create();
        $candidate = RecruitmentCandidate::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '600100200',
            'employee_id' => $employee->id,
        ]);

        $this->service()->terminate($employee, EmployeeTerminationReason::Other);
        $formerProcess = $candidate->processes()->where('status', RecruitmentStatus::BylyPracownik)->first();
        $this->assertNotNull($formerProcess);

        $this->service()->reinstate($employee->fresh());

        $employee->refresh();
        $this->assertFalse($employee->isTerminated());
        $this->assertNull($employee->termination_reason);
        $this->assertNull($employee->termination_note);

        // History is append-only — termination audit stays.
        $this->assertSame(RecruitmentStatus::BylyPracownik, $formerProcess->fresh()->status);

        $this->assertSame(2, $candidate->processes()->count());
        $this->assertTrue(
            $candidate->processes()
                ->where('status', RecruitmentStatus::Zatrudniony)
                ->where('employee_id', $employee->id)
                ->exists()
        );

        $reinstateLead = $candidate->leads()->latest('id')->first();
        $this->assertSame(RecruitmentReferralSource::EmployeeLifecycle, $reinstateLead->referral_source);
        $this->assertStringStartsWith('Przywrócenie pracownika – ', $reinstateLead->referral_source_detail);

        $this->assertTrue($candidate->fresh()->isHired());
        $this->assertFalse($candidate->fresh()->isFormerEmployee());
    }

    public function test_reinstate_without_linked_candidate_only_updates_employee(): void
    {
        $employee = Employee::factory()->create([
            'terminated_at' => now(),
            'termination_reason' => EmployeeTerminationReason::Other,
        ]);

        $this->service()->reinstate($employee);

        $employee->refresh();
        $this->assertFalse($employee->isTerminated());
        $this->assertNull($employee->termination_reason);
        $this->assertSame(0, RecruitmentProcess::count());
        $this->assertSame(0, RecruitmentLead::count());
    }
}
