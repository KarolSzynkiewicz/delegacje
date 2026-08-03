<?php

namespace Tests\Unit;

use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Services\EmployeeCandidateHireSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCandidateHireSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): EmployeeCandidateHireSyncService
    {
        return new EmployeeCandidateHireSyncService;
    }

    public function test_preview_classifies_missing_unhired_hired_and_no_phone(): void
    {
        $missing = Employee::factory()->create([
            'first_name' => 'Brak',
            'last_name' => 'Kandydata',
            'phone' => '600100200',
        ]);

        $unhiredEmployee = Employee::factory()->create([
            'first_name' => 'Jest',
            'last_name' => 'Niezatrudniony',
            'phone' => '+48 501 999 888',
        ]);
        $unhiredCandidate = RecruitmentCandidate::create([
            'first_name' => 'Jest',
            'last_name' => 'Niezatrudniony',
            'phone' => '501999888',
        ]);
        $lead = RecruitmentLead::create(['candidate_id' => $unhiredCandidate->id]);
        RecruitmentProcess::create([
            'lead_id' => $lead->id,
            'candidate_id' => $unhiredCandidate->id,
            'status' => RecruitmentStatus::Nowy,
        ]);

        $hiredEmployee = Employee::factory()->create([
            'first_name' => 'Juz',
            'last_name' => 'Zatrudniony',
            'phone' => '604555666',
        ]);
        $hiredCandidate = RecruitmentCandidate::create([
            'first_name' => 'Juz',
            'last_name' => 'Zatrudniony',
            'phone' => '604555666',
        ]);
        $hiredLead = RecruitmentLead::create(['candidate_id' => $hiredCandidate->id]);
        RecruitmentProcess::create([
            'lead_id' => $hiredLead->id,
            'candidate_id' => $hiredCandidate->id,
            'status' => RecruitmentStatus::Zatrudniony,
            'employee_id' => $hiredEmployee->id,
        ]);

        Employee::factory()->create([
            'first_name' => 'Bez',
            'last_name' => 'Telefonu',
            'phone' => null,
        ]);

        $byId = $this->service()->preview()->keyBy('employee_id');

        $this->assertSame('missing', $byId[$missing->id]['status']);
        $this->assertTrue($byId[$missing->id]['actionable']);
        $this->assertSame('48600100200', $byId[$missing->id]['phone']);

        $this->assertSame('unhired', $byId[$unhiredEmployee->id]['status']);
        $this->assertTrue($byId[$unhiredEmployee->id]['actionable']);
        $this->assertSame('48501999888', $byId[$unhiredEmployee->id]['phone']);

        $this->assertSame('hired', $byId[$hiredEmployee->id]['status']);
        $this->assertFalse($byId[$hiredEmployee->id]['actionable']);

        $noPhone = $byId->firstWhere('status', 'no_phone');
        $this->assertNotNull($noPhone);
        $this->assertFalse($noPhone['actionable']);
    }

    public function test_apply_creates_missing_marks_unhired_and_skips_hired(): void
    {
        $missing = Employee::factory()->create([
            'phone' => '600100200',
            'email' => null,
        ]);

        $unhiredEmployee = Employee::factory()->create(['phone' => '501999888']);
        $unhiredCandidate = RecruitmentCandidate::create([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'phone' => '501999888',
            'email' => 'anna@example.com',
        ]);
        $lead = RecruitmentLead::create(['candidate_id' => $unhiredCandidate->id]);
        RecruitmentProcess::create([
            'lead_id' => $lead->id,
            'candidate_id' => $unhiredCandidate->id,
            'status' => RecruitmentStatus::WTrakcieKontaktu,
        ]);

        $hiredEmployee = Employee::factory()->create(['phone' => '604555666']);
        $hiredCandidate = RecruitmentCandidate::create([
            'first_name' => 'Piotr',
            'last_name' => 'Wisniewski',
            'phone' => '604555666',
        ]);
        $hiredLead = RecruitmentLead::create(['candidate_id' => $hiredCandidate->id]);
        RecruitmentProcess::create([
            'lead_id' => $hiredLead->id,
            'candidate_id' => $hiredCandidate->id,
            'status' => RecruitmentStatus::Zatrudniony,
            'employee_id' => $hiredEmployee->id,
        ]);

        $preview = $this->service()->preview();
        $result = $this->service()->apply($preview);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['marked']);
        $this->assertGreaterThanOrEqual(1, $result['skipped']);

        $createdCandidate = RecruitmentCandidate::where('phone', '48600100200')->first();
        $this->assertNotNull($createdCandidate);
        $this->assertTrue(
            $createdCandidate->processes()
                ->where('status', RecruitmentStatus::Zatrudniony)
                ->where('employee_id', $missing->id)
                ->exists()
        );

        $this->assertSame(2, $unhiredCandidate->fresh()->processes()->count());
        $this->assertTrue(
            $unhiredCandidate->processes()
                ->where('status', RecruitmentStatus::Zatrudniony)
                ->where('employee_id', $unhiredEmployee->id)
                ->exists()
        );
        $this->assertSame('anna@example.com', $unhiredCandidate->fresh()->email);

        $this->assertSame(1, $hiredCandidate->fresh()->processes()->count());
    }

    public function test_apply_is_idempotent(): void
    {
        Employee::factory()->create(['phone' => '600100200']);

        $preview = $this->service()->preview();
        $first = $this->service()->apply($preview);
        $this->assertSame(1, $first['created']);

        $second = $this->service()->apply($this->service()->preview());
        $this->assertSame(0, $second['created']);
        $this->assertSame(0, $second['marked']);
        $this->assertSame(1, RecruitmentCandidate::where('phone', '48600100200')->count());
        $this->assertSame(
            1,
            RecruitmentProcess::where('status', RecruitmentStatus::Zatrudniony)->count()
        );
    }
}
