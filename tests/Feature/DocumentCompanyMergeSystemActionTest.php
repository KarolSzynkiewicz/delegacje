<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Services\DocumentCompanyMerge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentCompanyMergeSystemActionTest extends TestCase
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
        $this->actingAs($this->user);
    }

    public function test_system_actions_link_to_merge_and_cascade_delete(): void
    {
        $this->get(route('system-actions.index'))
            ->assertOk()
            ->assertSee('Połącz dokumenty')
            ->assertSee(route('system-actions.documents.merge'), false)
            ->assertSee('Usuń dokument i dzieci')
            ->assertSee(route('system-actions.documents.destroy'), false);
    }

    public function test_merge_copies_with_hard_link_skips_repeat_and_cascade_keeps_the_copy(): void
    {
        Storage::fake('public');

        $alpha = Company::create(['name' => 'Alpha', 'nip' => '1111111111']);
        $beta = Company::create(['name' => 'Beta', 'nip' => '2222222222']);
        $sourceA = Document::factory()->create([
            'name' => 'Umowa Alpha',
            'is_company_scoped' => false,
            'is_periodic' => false,
        ]);
        $sourceB = Document::factory()->create([
            'name' => 'Umowa Beta',
            'is_company_scoped' => false,
            'is_periodic' => false,
        ]);
        $employee = Employee::factory()->create();
        $path = 'employee_documents/'.$employee->id.'/a.pdf';
        Storage::disk('public')->put($path, 'plik-a');

        $entryA = EmployeeDocument::factory()->create([
            'employee_id' => $employee->id,
            'document_id' => $sourceA->id,
            'company_id' => null,
            'file_path' => $path,
            'kind' => 'bezokresowy',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
            'notes' => 'skan',
        ]);
        EmployeeDocument::factory()->create([
            'employee_id' => $employee->id,
            'document_id' => $sourceB->id,
            'company_id' => null,
            'file_path' => null,
            'kind' => 'bezokresowy',
            'valid_from' => '2026-02-01',
            'valid_to' => null,
        ]);

        $this->get(route('system-actions.documents.merge'))
            ->assertOk()
            ->assertSee('Umowa Alpha')
            ->assertSee('Nazwa dokumentu')
            ->assertSee('Dalej');

        $plan = $this->postJson(route('system-actions.documents.merge.plan'), [
            'name' => 'Umowa',
            'is_periodic' => '0',
            'is_company_scoped' => '1',
            'sources' => [$sourceA->id, $sourceB->id],
            'company' => [
                $sourceA->id => $alpha->id,
                $sourceB->id => $beta->id,
            ],
        ])->assertOk()->json();

        $target = Document::query()->where('name', 'Umowa')->first();
        $this->assertNotNull($target);

        $this->assertSame(2, $plan['total']);
        $this->assertFalse($plan['finished']);

        $state = $plan;
        while (! $state['finished']) {
            $state = $this->postJson(route('system-actions.documents.merge.chunk'), [
                'token' => $state['token'],
            ])->assertOk()->json();
        }

        $this->assertSame(2, $state['copied']);
        $this->assertSame(0, $state['skipped']);
        $this->assertTrue($target->fresh()->is_company_scoped);

        $copy = EmployeeDocument::query()->where('copied_from_id', $entryA->id)->first();
        $this->assertNotNull($copy);
        $this->assertSame($target->id, $copy->document_id);
        $this->assertSame($alpha->id, $copy->company_id);
        $this->assertSame('skan', $copy->notes);
        $this->assertNotSame($path, $copy->file_path);
        $this->assertSame('plik-a', Storage::disk('public')->get($copy->file_path));
        $this->assertSame(1, $state['linked']);
        $this->assertSame(
            fileinode(Storage::disk('public')->path($path)),
            fileinode(Storage::disk('public')->path($copy->file_path))
        );
        $this->assertNotNull($entryA->fresh());

        $again = $this->postJson(route('system-actions.documents.merge.plan'), [
            'name' => 'Umowa druga próba',
            'is_periodic' => '0',
            'is_company_scoped' => '1',
            'sources' => [$sourceA->id],
            'company' => [$sourceA->id => $alpha->id],
        ])->assertOk()->json();
        $again = $this->postJson(route('system-actions.documents.merge.chunk'), [
            'token' => $again['token'],
        ])->assertOk()->json();

        $this->assertTrue($again['finished']);
        $this->assertSame(0, $again['copied']);
        $this->assertSame(1, $again['skipped']);
        $this->assertSame(1, EmployeeDocument::query()->where('copied_from_id', $entryA->id)->count());

        $this->post(route('system-actions.documents.destroy.store'), [
            'document_id' => $sourceA->id,
        ])->assertRedirect(route('system-actions.documents.destroy'));

        $this->assertDatabaseMissing('documents', ['id' => $sourceA->id]);
        $this->assertDatabaseMissing('employee_documents', ['id' => $entryA->id]);
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertTrue(Storage::disk('public')->exists($copy->file_path));
        $this->assertSame('plik-a', Storage::disk('public')->get($copy->file_path));
        $this->assertNull($copy->fresh()->copied_from_id);
        $this->assertNotNull($target->fresh());
    }

    public function test_merge_rejects_duplicate_target_name(): void
    {
        $company = Company::create(['name' => 'Alpha', 'nip' => '1111111111']);
        $source = Document::factory()->create(['name' => 'Umowa Alpha', 'is_periodic' => false]);
        Document::factory()->create(['name' => 'Umowa']);

        $this->postJson(route('system-actions.documents.merge.plan'), [
            'name' => 'Umowa',
            'is_periodic' => '0',
            'sources' => [$source->id],
            'company' => [$source->id => $company->id],
        ])->assertStatus(422)->assertJsonValidationErrors('name');

        $this->assertSame(1, Document::query()->where('name', 'Umowa')->count());
    }

    public function test_merge_advances_ten_rows_per_chunk(): void
    {
        $company = Company::create(['name' => 'Alpha', 'nip' => '1111111111']);
        $target = Document::factory()->create([
            'name' => 'Umowa',
            'is_company_scoped' => true,
            'is_periodic' => false,
        ]);
        $source = Document::factory()->create([
            'name' => 'Umowa stara',
            'is_periodic' => false,
        ]);
        $employee = Employee::factory()->create();

        foreach (range(1, 11) as $day) {
            EmployeeDocument::factory()->create([
                'employee_id' => $employee->id,
                'document_id' => $source->id,
                'company_id' => null,
                'file_path' => null,
                'kind' => 'bezokresowy',
                'valid_from' => sprintf('2026-01-%02d', $day),
                'valid_to' => null,
            ]);
        }

        $merge = app(DocumentCompanyMerge::class);
        $state = $merge->start($target->id, [$source->id => $company->id]);
        $this->assertSame(11, $state['total']);
        $this->assertFalse($state['finished']);

        $state = $merge->advance($state['token']);
        $this->assertSame(10, $state['done']);
        $this->assertSame(10, $state['copied']);
        $this->assertFalse($state['finished']);

        $state = $merge->advance($state['token']);
        $this->assertSame(11, $state['done']);
        $this->assertTrue($state['finished']);
        $this->assertSame(11, EmployeeDocument::query()->where('document_id', $target->id)->count());
        $this->assertSame(11, EmployeeDocument::query()->where('document_id', $source->id)->count());
    }
}
