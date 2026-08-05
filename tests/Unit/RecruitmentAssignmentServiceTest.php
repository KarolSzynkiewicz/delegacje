<?php

namespace Tests\Unit;

use App\Enums\RecruitmentStatus;
use App\Models\RecruitmentAssignmentHistory;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\User;
use App\Services\RecruitmentAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RecruitmentAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): RecruitmentAssignmentService
    {
        return new RecruitmentAssignmentService();
    }

    private function createProcess(
        RecruitmentStatus $status = RecruitmentStatus::Nowy,
        ?int $assignedRecruiterId = null,
        ?string $createdAt = null
    ): RecruitmentProcess {
        $candidate = RecruitmentCandidate::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '600'.random_int(100000, 999999),
        ]);
        $lead = RecruitmentLead::create(['candidate_id' => $candidate->id]);

        $process = RecruitmentProcess::create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => $status,
            'assigned_recruiter_id' => $assignedRecruiterId,
        ]);

        if ($createdAt !== null) {
            $process->created_at = $createdAt;
            $process->save();
        }

        return $process;
    }

    public function test_calculate_even_distribution_splits_130_between_two_recruiters(): void
    {
        $distribution = $this->service()->calculateEvenDistribution(130, [1, 2]);

        $this->assertSame([1 => 65, 2 => 65], $distribution);
    }

    public function test_calculate_even_distribution_handles_remainder(): void
    {
        $distribution = $this->service()->calculateEvenDistribution(131, [1, 2]);

        $this->assertSame([1 => 66, 2 => 65], $distribution);
    }

    public function test_get_unassigned_counts_by_status(): void
    {
        $this->createProcess(RecruitmentStatus::Nowy);
        $this->createProcess(RecruitmentStatus::Nowy);
        $this->createProcess(RecruitmentStatus::WTrakcieKontaktu);

        $recruiter = User::factory()->create();
        $this->createProcess(RecruitmentStatus::Nowy, $recruiter->id);

        $counts = $this->service()->getUnassignedCountsByStatus();

        $this->assertSame(2, $counts[RecruitmentStatus::Nowy->value]);
        $this->assertSame(1, $counts[RecruitmentStatus::WTrakcieKontaktu->value]);
    }

    public function test_distribute_assigns_processes_and_creates_history(): void
    {
        $changedBy = User::factory()->create();
        $recruiterA = User::factory()->create();
        $recruiterB = User::factory()->create();

        $processIds = [];
        for ($i = 0; $i < 4; $i++) {
            $processIds[] = $this->createProcess(
                RecruitmentStatus::Nowy,
                null,
                sprintf('2026-01-0%d 10:00:00', $i + 1)
            )->id;
        }

        $result = $this->service()->distribute(
            $processIds,
            [$recruiterA->id => 2, $recruiterB->id => 2],
            $changedBy->id
        );

        $this->assertSame(4, $result['assigned']);
        $this->assertSame(2, $result['by_recruiter'][$recruiterA->id]);
        $this->assertSame(2, $result['by_recruiter'][$recruiterB->id]);
        $this->assertSame(4, RecruitmentAssignmentHistory::count());

        $this->assertSame(
            2,
            RecruitmentProcess::where('assigned_recruiter_id', $recruiterA->id)->count()
        );
    }

    public function test_distribute_rejects_mismatched_totals(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $process = $this->createProcess();
        $recruiter = User::factory()->create();

        $this->service()->distribute(
            [$process->id],
            [$recruiter->id => 2],
            User::factory()->create()->id
        );
    }

    public function test_vacation_mode_fetches_from_specific_recruiter(): void
    {
        $onLeave = User::factory()->create();
        $other = User::factory()->create();

        $fromLeave = $this->createProcess(RecruitmentStatus::Zaakceptowany, $onLeave->id);
        $this->createProcess(RecruitmentStatus::Zaakceptowany, $other->id);
        $this->createProcess(RecruitmentStatus::Zaakceptowany);

        $ids = $this->service()->fetchProcessIds(
            [RecruitmentStatus::Zaakceptowany->value],
            $onLeave->id
        );

        $this->assertSame([$fromLeave->id], $ids);
    }

    public function test_assign_recruiter_is_no_op_when_unchanged(): void
    {
        $recruiter = User::factory()->create();
        $process = $this->createProcess(RecruitmentStatus::Nowy, $recruiter->id);

        $process->assignRecruiter($recruiter->id, User::factory()->create()->id);

        $this->assertSame(0, RecruitmentAssignmentHistory::count());
    }

    public function test_assign_recruiter_records_history_on_change(): void
    {
        $from = User::factory()->create();
        $to = User::factory()->create();
        $changedBy = User::factory()->create();

        $process = $this->createProcess(RecruitmentStatus::Nowy, $from->id);
        $process->assignRecruiter($to->id, $changedBy->id);

        $this->assertSame($to->id, $process->fresh()->assigned_recruiter_id);

        $history = RecruitmentAssignmentHistory::first();
        $this->assertSame($from->id, $history->from_recruiter_id);
        $this->assertSame($to->id, $history->to_recruiter_id);
        $this->assertSame($changedBy->id, $history->changed_by);
    }
}
