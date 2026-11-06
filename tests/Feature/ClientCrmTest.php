<?php

namespace Tests\Feature;

use App\Enums\ClientContactChannel;
use App\Enums\ClientContactOutcome;
use App\Enums\ClientContactPurpose;
use App\Enums\ClientType;
use App\Livewire\ClientTabs;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\TimeLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientCrmTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_create_client_and_attach_project(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => 'MSC Mediterranean',
                'type' => ClientType::Client->value,
                'short_name' => 'MSC',
                'country' => 'PL',
                'is_active' => '1',
            ]);

        $client = Client::query()->where('name', 'MSC Mediterranean')->first();
        $this->assertNotNull($client);
        $this->assertSame(ClientType::Client, $client->type);
        $response->assertRedirect(route('clients.show', $client));

        $project = Project::factory()->create([
            'client_id' => $client->id,
            'client_name' => $client->name,
        ]);

        $this->assertSame($client->id, $project->fresh()->client_id);
        $this->assertTrue($client->projects()->whereKey($project->id)->exists());
    }

    public function test_service_provider_has_no_project_tabs_and_cannot_own_projects(): void
    {
        $provider = Client::factory()->serviceProvider()->create([
            'name' => 'Warsztat Demo',
        ]);

        $this->assertFalse($provider->canOwnProjects());

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $provider])
            ->assertSee('Usługodawca')
            ->assertDontSeeHtml("setTab('projects')")
            ->assertDontSeeHtml("setTab('hour_audit')");

        $client = Client::factory()->create();
        Project::factory()->create([
            'client_id' => $client->id,
            'client_name' => $client->name,
        ]);

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client->fresh()])
            ->call('updateClientField', 'type', ClientType::ServiceProvider->value)
            ->assertHasErrors(['type']);

        $this->assertSame(ClientType::Client, $client->fresh()->type);

        $this->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => 'Podwykonawca ABC',
                'type' => ClientType::ServiceProvider->value,
                'country' => 'PL',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'name' => 'Podwykonawca ABC',
            'type' => ClientType::ServiceProvider->value,
        ]);
    }

    public function test_client_hour_audit_lists_discrepancies_across_projects(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10'));

        $client = Client::factory()->create(['name' => 'Audyt Klient']);
        $projectA = Project::factory()->create(['client_id' => $client->id, 'client_name' => $client->name]);
        $projectB = Project::factory()->create(['client_id' => $client->id, 'client_name' => $client->name]);
        $other = Project::factory()->create();

        $this->makeMismatchLog($projectA, 8.0, 6.0);
        $this->makeMismatchLog($projectB, 8.0, 7.0);
        $this->makeMismatchLog($other, 8.0, 5.0);

        $rows = app(TimeLogService::class)->getHourDiscrepancies(
            '2026-11',
            $client->projects()->pluck('id')->all()
        );

        $this->assertCount(2, $rows);
        $projectNames = collect($rows)->pluck('project_name')->all();
        $this->assertContains($projectA->name, $projectNames);
        $this->assertContains($projectB->name, $projectNames);
        $this->assertNotContains($other->name, $projectNames);

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client])
            ->set('activeTab', 'hour_audit')
            ->set('auditMonth', '2026-11')
            ->assertSee('Audyt niezgodności godzin')
            ->assertSee($projectA->name)
            ->assertSee($projectB->name)
            ->assertDontSee($other->name, false);
    }

    public function test_cannot_delete_client_with_projects(): void
    {
        $client = Client::factory()->create();
        Project::factory()->create(['client_id' => $client->id, 'client_name' => $client->name]);

        $this->actingAs($this->user)
            ->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.show', $client));

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_can_manage_contacts_and_register_project_attempt(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05 12:00:00'));

        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'client_name' => $client->name,
        ]);

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client])
            ->call('openContactForm')
            ->set('contactName', 'Anna Kowalska')
            ->set('contactRole', 'PM klienta')
            ->set('contactEmail', 'anna@example.com')
            ->set('contactPhone', '+48123123123')
            ->set('contactIsPrimary', true)
            ->call('saveContact')
            ->assertHasNoErrors();

        $contact = ClientContact::query()->where('client_id', $client->id)->first();
        $this->assertNotNull($contact);
        $this->assertTrue($contact->is_primary);
        $this->assertSame('Anna Kowalska', $client->fresh()->contact_person);

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client->fresh()])
            ->call('openAttemptForm', $project->id)
            ->set('attemptContactId', $contact->id)
            ->set('attemptChannel', ClientContactChannel::Phone->value)
            ->set('attemptPurpose', ClientContactPurpose::HoursSettlement->value)
            ->set('attemptOutcome', ClientContactOutcome::Agreed->value)
            ->set('attemptContactedAt', '2026-11-05T10:30')
            ->set('attemptSummary', 'Uzgodniono 7h za 4.11')
            ->call('saveAttempt')
            ->assertHasNoErrors()
            ->assertSet('showAttemptForm', false)
            ->set('activeTab', 'attempts')
            ->assertSee('Uzgodniono 7h za 4.11')
            ->assertSee($project->name);

        $this->assertDatabaseHas('client_contact_attempts', [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'client_contact_id' => $contact->id,
            'outcome' => ClientContactOutcome::Agreed->value,
        ]);

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client->fresh()])
            ->set('activeTab', 'projects')
            ->assertSee('Ostatni kontakt')
            ->assertSee('05.11.2026');
    }

    public function test_inline_client_and_contact_updates(): void
    {
        $client = Client::factory()->create(['name' => 'Stara Nazwa', 'city' => 'Gdańsk']);
        $contact = $client->contacts()->create([
            'name' => 'Jan Test',
            'role' => 'PM',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'client_name' => $client->name,
        ]);

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client])
            ->call('updateClientField', 'name', 'Nowa Nazwa Sp. z o.o.')
            ->call('updateClientField', 'city', 'Gdynia')
            ->call('updateContactField', $contact->id, 'phone', '+48 111 222 333')
            ->assertHasNoErrors();

        $this->assertSame('Nowa Nazwa Sp. z o.o.', $client->fresh()->name);
        $this->assertSame('Gdynia', $client->fresh()->city);
        $this->assertSame('+48 111 222 333', $contact->fresh()->phone);
        $this->assertSame('Nowa Nazwa Sp. z o.o.', $project->fresh()->client_name);
    }

    public function test_follow_up_queue_complete_snooze_and_edit(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05 12:00:00'));

        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'client_name' => $client->name,
        ]);

        $attempt = $client->contactAttempts()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'channel' => ClientContactChannel::Phone->value,
            'purpose' => ClientContactPurpose::HoursSettlement->value,
            'outcome' => ClientContactOutcome::Callback->value,
            'contacted_at' => now()->subDay(),
            'next_action_at' => now()->subHour(),
            'summary' => 'Oddzwonić w sprawie godzin',
        ]);

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client])
            ->assertSee('Oddzwonić w sprawie godzin')
            ->assertSee('zaległe')
            ->call('snoozeFollowUp', $attempt->id, 3)
            ->assertHasNoErrors();

        $attempt->refresh();
        $this->assertTrue($attempt->next_action_at->gt(now()));
        $this->assertTrue($attempt->next_action_at->equalTo(
            now()->addDays(3)->setTime(9, 0)
        ));

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client->fresh()])
            ->call('updateAttemptField', $attempt->id, 'outcome', ClientContactOutcome::Agreed->value)
            ->call('updateAttemptField', $attempt->id, 'summary', 'Uzgodnione po oddzwonieniu')
            ->call('updateAttemptField', $attempt->id, 'next_action_at', '')
            ->assertHasNoErrors();

        $attempt->refresh();
        $this->assertSame(ClientContactOutcome::Agreed, $attempt->outcome);
        $this->assertSame('Uzgodnione po oddzwonieniu', $attempt->summary);
        $this->assertNull($attempt->next_action_at);

        $open = $client->contactAttempts()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'channel' => ClientContactChannel::Phone->value,
            'purpose' => ClientContactPurpose::FollowUp->value,
            'outcome' => ClientContactOutcome::Reached->value,
            'contacted_at' => now(),
            'next_action_at' => now()->addDay(),
            'summary' => 'Jeszcze jeden temat',
        ]);

        Livewire::actingAs($this->user)
            ->test(ClientTabs::class, ['client' => $client->fresh()])
            ->call('completeFollowUp', $open->id)
            ->assertHasNoErrors();

        $this->assertNull($open->fresh()->next_action_at);
    }

    private function makeMismatchLog(Project $project, float $hoursWorked, float $clientHours): TimeLog
    {
        $employee = Employee::factory()->create();
        $role = Role::query()->first() ?? Role::factory()->create();

        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);

        return TimeLog::create([
            'project_assignment_id' => $assignment->id,
            'start_time' => Carbon::parse('2026-11-05 08:00:00'),
            'end_time' => Carbon::parse('2026-11-05 08:00:00')->addHours($hoursWorked),
            'hours_worked' => $hoursWorked,
            'client_hours' => $clientHours,
        ]);
    }
}
