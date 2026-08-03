<?php

namespace Tests\Unit;

use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
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

    public function test_preview_classifies_missing_unhired_hired_conflict_and_no_phone(): void
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

        // Already linked via FK — the source of truth, regardless of process history.
        $hiredEmployee = Employee::factory()->create([
            'first_name' => 'Juz',
            'last_name' => 'Zatrudniony',
            'phone' => '604555666',
        ]);
        RecruitmentCandidate::create([
            'first_name' => 'Juz',
            'last_name' => 'Zatrudniony',
            'phone' => '604555666',
            'employee_id' => $hiredEmployee->id,
        ]);

        // Same phone as an existing candidate, but that candidate is linked to a
        // DIFFERENT employee — must be flagged as a conflict, not auto-fixed.
        $conflictEmployee = Employee::factory()->create([
            'first_name' => 'Konflikt',
            'last_name' => 'Telefonu',
            'phone' => '699111222',
        ]);
        $otherEmployee = Employee::factory()->create(['phone' => '111222333']);
        RecruitmentCandidate::create([
            'first_name' => 'Inny',
            'last_name' => 'Wlasciciel',
            'phone' => '699111222',
            'employee_id' => $otherEmployee->id,
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
        $this->assertSame($unhiredCandidate->id, $byId[$unhiredEmployee->id]['candidate_id']);

        $this->assertSame('hired', $byId[$hiredEmployee->id]['status']);
        $this->assertFalse($byId[$hiredEmployee->id]['actionable']);

        $this->assertSame('conflict', $byId[$conflictEmployee->id]['status']);
        $this->assertFalse($byId[$conflictEmployee->id]['actionable']);

        $noPhone = $byId->firstWhere('status', 'no_phone');
        $this->assertNotNull($noPhone);
        $this->assertFalse($noPhone['actionable']);
    }

    public function test_apply_creates_missing_links_unhired_and_skips_hired_and_conflict(): void
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

        $hiredEmployee = Employee::factory()->create(['phone' => '604555666']);
        RecruitmentCandidate::create([
            'first_name' => 'Piotr',
            'last_name' => 'Wisniewski',
            'phone' => '604555666',
            'employee_id' => $hiredEmployee->id,
        ]);

        $conflictEmployee = Employee::factory()->create(['phone' => '699111222']);
        $otherEmployee = Employee::factory()->create(['phone' => '111222333']);
        RecruitmentCandidate::create([
            'first_name' => 'Inny',
            'last_name' => 'Wlasciciel',
            'phone' => '699111222',
            'employee_id' => $otherEmployee->id,
        ]);

        $preview = $this->service()->preview();
        $result = $this->service()->apply($preview);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['marked']);
        $this->assertGreaterThanOrEqual(2, $result['skipped']); // hired + conflict

        $createdCandidate = RecruitmentCandidate::where('phone', '48600100200')->first();
        $this->assertNotNull($createdCandidate);
        $this->assertSame($missing->id, $createdCandidate->employee_id);
        $this->assertTrue(
            $createdCandidate->processes()
                ->where('status', RecruitmentStatus::Zatrudniony)
                ->where('employee_id', $missing->id)
                ->exists()
        );

        $this->assertSame($unhiredEmployee->id, $unhiredCandidate->fresh()->employee_id);
        $this->assertSame('anna@example.com', $unhiredCandidate->fresh()->email);
        $this->assertTrue(
            $unhiredCandidate->processes()
                ->where('status', RecruitmentStatus::Zatrudniony)
                ->where('employee_id', $unhiredEmployee->id)
                ->exists()
        );

        // Conflict candidate stays linked to the original owner — untouched.
        $this->assertSame(
            $otherEmployee->id,
            RecruitmentCandidate::where('phone', '48699111222')->first()->employee_id
        );
        $this->assertFalse(
            RecruitmentCandidate::where('employee_id', $conflictEmployee->id)->exists()
        );
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
    }
}
