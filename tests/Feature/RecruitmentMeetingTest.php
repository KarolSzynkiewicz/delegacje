<?php

namespace Tests\Feature;

use App\Enums\RecruitmentStatus;
use App\Enums\TaskStatus;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Livewire\RecruitmentProcessesTable;
use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecruitmentMeetingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'Admin Spotkań']);
        $this->user->assignRole('administrator');
        $this->actingAs($this->user);
    }

    public function test_schedule_meeting_button_shows_only_on_contact_stage(): void
    {
        $contact = $this->createProcess(RecruitmentStatus::WTrakcieKontaktu, 'Kontaktowy', 'UnikatSpotkanie');
        $fresh = $this->createProcess(RecruitmentStatus::Nowy, 'Nowy', 'UnikatNowySpotkanie');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $contact->id])
            ->assertSee('Umów spotkanie rekrutacyjne')
            ->call('previewStage', RecruitmentStatus::Zaakceptowany->value)
            ->assertDontSee('Umów spotkanie rekrutacyjne');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $fresh->id])
            ->assertDontSee('Umów spotkanie rekrutacyjne');
    }

    public function test_scheduling_a_meeting_creates_a_meeting_work_item_and_replaces_the_button_with_the_date(): void
    {
        $colleague = User::factory()->create(['name' => 'Kolega Rekruter']);
        $process = $this->createProcess(RecruitmentStatus::WTrakcieKontaktu, 'Eryk', 'Eryk');
        $process->update(['assigned_recruiter_id' => $this->user->id]);
        $cardUrl = route('recruitment-processes.show', $process);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Umów spotkanie rekrutacyjne')
            ->call('openMeetingModal')
            ->assertSet('showMeetingModal', true)
            ->set('meetingDate', '2026-09-15')
            ->set('meetingStart', '10:00')
            ->set('meetingEnd', '11:30')
            ->set('meetingParticipantIds', [$this->user->id, $colleague->id])
            ->set('meetingNote', 'Omówić stawkę')
            ->call('saveMeeting')
            ->assertSet('showMeetingModal', false)
            ->assertDontSee('Umów spotkanie rekrutacyjne')
            ->assertSee('15.09.2026')
            ->assertSee('10:00')
            ->assertSee('Odbyło się');

        $this->assertSame(RecruitmentStatus::Zaakceptowany, $process->fresh()->status);

        $task = ProjectTask::query()->where('recruitment_process_id', $process->id)->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->isMeeting());
        $this->assertFalse($task->isCallback());
        $this->assertSame('Spotkanie rekrutacyjne: Eryk Eryk #'.$process->id, $task->name);
        $this->assertSame('2026-09-15 10:00:00', $task->starts_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-15 11:30:00', $task->ends_at?->format('Y-m-d H:i:s'));
        $this->assertEqualsCanonicalizing([$this->user->id, $colleague->id], $task->participant_ids);
        $this->assertStringContainsString('Kandydat: Eryk Eryk', (string) $task->description);
        $this->assertStringContainsString($cardUrl, (string) $task->description);
        $this->assertStringContainsString('Omówić stawkę', (string) $task->description);

        $item = WorkItem::query()->where('source_id', $task->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(WorkItemType::Meeting, $item->type);

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Spotkanie')
            ->assertSee('Eryk Eryk')
            ->assertSee('Kolega Rekruter')
            ->assertSee($cardUrl)
            ->assertDontSee('Dziennik operacyjny');

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Spotkanie rekrutacyjne: Eryk Eryk')
            ->assertSee('Spotkanie');
    }

    public function test_completed_meeting_keeps_the_date_as_information(): void
    {
        $process = $this->createProcess(RecruitmentStatus::WTrakcieKontaktu, 'Marek', 'Spotkaniowy');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('openMeetingModal')
            ->set('meetingDate', '2026-09-15')
            ->set('meetingStart', '10:00')
            ->set('meetingEnd', '11:00')
            ->set('meetingParticipantIds', [$this->user->id])
            ->call('saveMeeting')
            ->assertDontSee('Umów spotkanie rekrutacyjne')
            ->assertSee('15.09.2026');

        $task = ProjectTask::query()->where('recruitment_process_id', $process->id)->first();
        $this->assertNotNull($task);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('toggleTaskDone', $task->id)
            ->assertSee('15.09.2026')
            ->assertSee('Odbyte')
            ->assertDontSee('Umów spotkanie rekrutacyjne');

        $this->assertSame(TaskStatus::COMPLETED, $task->fresh()->status);
    }

    public function test_verification_stage_can_mark_the_meeting_as_done(): void
    {
        $process = $this->createProcess(RecruitmentStatus::WTrakcieKontaktu, 'Ola', 'WeryfikacjaSpotkanie');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('openMeetingModal')
            ->set('meetingDate', '2026-09-18')
            ->set('meetingStart', '14:00')
            ->set('meetingEnd', '15:00')
            ->set('meetingParticipantIds', [$this->user->id])
            ->call('saveMeeting');

        $task = ProjectTask::query()->where('recruitment_process_id', $process->id)->first();
        $this->assertNotNull($task);
        $this->assertSame(RecruitmentStatus::Zaakceptowany, $process->fresh()->status);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Odbyło się')
            ->assertSee('18.09.2026')
            ->assertDontSee('Umów spotkanie rekrutacyjne')
            ->call('toggleTaskDone', $task->id)
            ->assertSee('18.09.2026')
            ->assertSee('Odbyte')
            ->assertDontSee('Odbyło się');

        $this->assertSame(TaskStatus::COMPLETED, $task->fresh()->status);
        $this->assertSame(WorkItemStatus::Completed, WorkItem::query()->where('source_id', $task->id)->first()?->status);
    }

    public function test_tasks_grid_footer_can_schedule_a_meeting(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Umów spotkanie')
            ->call('startAdd', 'meeting')
            ->assertSet('addKind', 'meeting')
            ->set('newTaskName', 'Sync z zespołem')
            ->set('newMeetingDate', '2026-09-16')
            ->set('newMeetingStart', '09:00')
            ->set('newMeetingEnd', '10:00')
            ->set('newMeetingParticipantIds', [$this->user->id])
            ->call('submitAdd')
            ->assertSee('Sync z zespołem')
            ->assertSee('Spotkanie');

        $task = ProjectTask::query()->where('name', 'Sync z zespołem')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->isMeeting());
        $this->assertSame('2026-09-16 09:00:00', $task->starts_at?->format('Y-m-d H:i:s'));
        $this->assertEqualsCanonicalizing([$this->user->id], $task->participant_ids);
        $this->assertSame(WorkItemType::Meeting, WorkItem::query()->where('source_id', $task->id)->first()?->type);
    }

    private function createProcess(RecruitmentStatus $status, string $first, string $last): RecruitmentProcess
    {
        $candidate = RecruitmentCandidate::create([
            'first_name' => $first,
            'last_name' => $last,
            'phone' => '600'.random_int(100000, 999999),
        ]);
        $lead = RecruitmentLead::create(['candidate_id' => $candidate->id]);

        return RecruitmentProcess::create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => $status,
            'assigned_recruiter_id' => $this->user->id,
        ]);
    }
}
