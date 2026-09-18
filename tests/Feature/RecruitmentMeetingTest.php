<?php

namespace Tests\Feature;

use App\Enums\RecruitmentStatus;
use App\Enums\TaskStatus;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Livewire\RecruitmentProcessesTable;
use App\Livewire\TasksGrid;
use App\Livewire\WorkItemPlan;
use App\Models\ProjectTask;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\WorkItemPlanService;
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
            ->assertSee('Umów spotkanie')
            ->call('previewStage', RecruitmentStatus::Zaakceptowany->value)
            ->assertDontSee('Umów spotkanie');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $fresh->id])
            ->assertDontSee('Umów spotkanie');
    }

    public function test_scheduling_a_meeting_creates_a_hanging_work_item_and_opens_the_plan(): void
    {
        $colleague = User::factory()->create(['name' => 'Kolega Rekruter']);
        $process = $this->createProcess(RecruitmentStatus::WTrakcieKontaktu, 'Eryk', 'Eryk');
        $process->update(['assigned_recruiter_id' => $this->user->id]);
        $cardUrl = route('recruitment-processes.show', $process);

        $component = Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Umów spotkanie')
            ->call('openMeetingModal')
            ->assertSet('showMeetingModal', true)
            ->set('meetingParticipantIds', [$this->user->id, $colleague->id])
            ->set('meetingLocation', $this->teamsJoinUrl())
            ->set('meetingNote', 'Omówić stawkę')
            ->call('saveMeeting');

        $this->assertSame(RecruitmentStatus::Zaakceptowany, $process->fresh()->status);

        $task = ProjectTask::query()->where('recruitment_process_id', $process->id)->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->isMeeting());
        $this->assertFalse($task->isCallback());
        $this->assertSame('Spotkanie: Eryk Eryk', $task->name);
        $this->assertNull($task->starts_at);
        $this->assertNull($task->ends_at);
        $this->assertEqualsCanonicalizing([$this->user->id, $colleague->id], $task->participant_ids);
        $this->assertSame($this->teamsJoinUrl(), $task->location);
        $this->assertStringContainsString('Kandydat: Eryk Eryk', (string) $task->description);
        $this->assertStringContainsString($cardUrl, (string) $task->description);
        $this->assertStringContainsString('Omówić stawkę', (string) $task->description);

        $item = WorkItem::query()->where('source_id', $task->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(WorkItemType::Meeting, $item->type);

        $component->assertRedirect(route('work-items.plan', ['u' => $this->user->id, 'pin' => $item->id]));

        ProjectTask::query()->create([
            'name' => 'Inne zadanie z kolejki',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class, ['userId' => $this->user->id, 'pinId' => $item->id])
            ->assertSee('Spotkanie: Eryk Eryk')
            ->assertSee('przeciągnij na godzinę', false)
            ->assertDontSee('Inne zadanie z kolejki');

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Spotkanie')
            ->assertSee('umówił spotkanie')
            ->assertSee('Bez terminu')
            ->assertDontSee('spotkanie rekrutacyjne')
            ->assertSee('Eryk Eryk')
            ->assertSee('Kolega Rekruter')
            ->assertSee($cardUrl)
            ->assertSee('Gdzie')
            ->assertSee($this->teamsJoinUrl(), false)
            ->assertDontSee('Dziennik operacyjny')
            ->assertDontSee('Dodaj do kalendarza');

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Spotkanie: Eryk Eryk')
            ->assertSee('Spotkanie');
    }

    public function test_completed_meeting_keeps_the_date_as_information(): void
    {
        $process = $this->createProcess(RecruitmentStatus::WTrakcieKontaktu, 'Marek', 'Spotkaniowy');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('openMeetingModal')
            ->set('meetingParticipantIds', [$this->user->id])
            ->call('saveMeeting')
            ->assertRedirect();

        $task = ProjectTask::query()->where('recruitment_process_id', $process->id)->first();
        $this->assertNotNull($task);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('toggleTaskDone', $task->id)
            ->assertSee('Bez terminu')
            ->assertSee('Odbyte')
            ->assertDontSee('Umów spotkanie');

        $this->assertSame(TaskStatus::COMPLETED, $task->fresh()->status);
    }

    public function test_verification_stage_can_mark_the_meeting_as_done(): void
    {
        $process = $this->createProcess(RecruitmentStatus::WTrakcieKontaktu, 'Ola', 'WeryfikacjaSpotkanie');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('openMeetingModal')
            ->set('meetingParticipantIds', [$this->user->id])
            ->call('saveMeeting')
            ->assertRedirect();

        $task = ProjectTask::query()->where('recruitment_process_id', $process->id)->first();
        $this->assertNotNull($task);
        $this->assertSame(RecruitmentStatus::Zaakceptowany, $process->fresh()->status);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Odbyło się')
            ->assertSee('Bez terminu')
            ->assertDontSee('Umów spotkanie')
            ->call('toggleTaskDone', $task->id)
            ->assertSee('Bez terminu')
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
            ->assertSee('Uczestnicy')
            ->assertSee('Gdzie')
            ->set('newTaskName', 'Sync z zespołem')
            ->set('newMeetingDate', '2026-09-16')
            ->set('newMeetingStart', '09:00')
            ->set('newMeetingEnd', '10:00')
            ->set('newMeetingParticipantIds', [$this->user->id])
            ->set('newMeetingLocation', 'Sala 2 / Gdańsk')
            ->call('submitAdd')
            ->assertSee('Sync z zespołem')
            ->assertSee('Spotkanie');

        $task = ProjectTask::query()->where('name', 'Sync z zespołem')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->isMeeting());
        $this->assertSame('2026-09-16 09:00:00', $task->starts_at?->format('Y-m-d H:i:s'));
        $this->assertSame('Sala 2 / Gdańsk', $task->location);
        $this->assertEqualsCanonicalizing([$this->user->id], $task->participant_ids);
        $this->assertSame(WorkItemType::Meeting, WorkItem::query()->where('source_id', $task->id)->first()?->type);

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('umówił spotkanie')
            ->assertDontSee('Kandydat')
            ->assertSee('Sala 2 / Gdańsk')
            ->assertSee('Gdzie')
            ->assertDontSee('Dodaj do kalendarza');
    }

    public function test_recruitment_meeting_without_a_slot_stays_linked_and_lands_in_the_plan_queue(): void
    {
        $process = $this->createProcess(RecruitmentStatus::WTrakcieKontaktu, 'Iga', 'BezTerminu');

        $component = Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('openMeetingModal')
            ->set('meetingParticipantIds', [$this->user->id])
            ->set('meetingNote', 'Najpierw znajdziemy slot')
            ->call('saveMeeting');

        $this->assertSame(RecruitmentStatus::Zaakceptowany, $process->fresh()->status);

        $task = ProjectTask::query()->where('recruitment_process_id', $process->id)->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->isMeeting());
        $this->assertNull($task->starts_at);
        $this->assertNull($task->ends_at);

        $item = WorkItem::query()->where('source_id', $task->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(WorkItemType::Meeting, $item->type);
        $component->assertRedirect(route('work-items.plan', ['u' => $this->user->id, 'pin' => $item->id]));
        $queue = app(WorkItemPlanService::class)->queue($this->user, now());
        $this->assertTrue($queue->contains(fn (WorkItem $row) => $row->id === $item->id));

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Bez terminu')
            ->assertSee('Spotkanie: Iga BezTerminu')
            ->assertDontSee('Umów spotkanie');

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Bez terminu')
            ->assertSee('umówił spotkanie');
    }

    private function teamsJoinUrl(): string
    {
        return 'https://teams.microsoft.com/l/meetup-join/19%3ameeting_abcdefghijklmnopqrstuvwxyz0123456789ABCDEF%40thread.v2/0?context=%7b%22Tid%22%3a%22aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee%22%2c%22Oid%22%3a%22ffffffff-1111-2222-3333-444444444444%22%7d';
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
