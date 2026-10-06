<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyAssignment;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentCompanyRequirementTest extends TestCase
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

    public function test_company_lives_on_employee_document_not_dictionary(): void
    {
        $this->assertFalse(Schema::hasColumn('documents', 'company_id'));
        $this->assertTrue(Schema::hasColumn('documents', 'is_company_scoped'));
        $this->assertTrue(Schema::hasColumn('employee_documents', 'company_id'));
        $this->assertTrue(Schema::hasColumn('documents', 'name'));
    }

    public function test_dictionary_name_stays_globally_unique(): void
    {
        $this->post(route('documents.store'), [
            'name' => 'Umowa o pracę',
            'is_periodic' => '0',
            'is_required' => '1',
            'is_company_scoped' => '1',
        ])->assertRedirect(route('documents.index'));

        $this->from(route('documents.create'))
            ->post(route('documents.store'), [
                'name' => 'Umowa o pracę',
                'is_periodic' => '0',
                'is_required' => '1',
                'is_company_scoped' => '1',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('documents', 1);
        $this->get(route('documents.index'))
            ->assertOk()
            ->assertSee('Umowa o pracę')
            ->assertSee('Na spółkę');
    }

    public function test_company_scoped_requirement_needs_instance_for_current_company(): void
    {
        Carbon::setTestNow('2026-09-15');

        $alpha = Company::create(['name' => 'Alpha', 'nip' => '1111111111']);
        $beta = Company::create(['name' => 'Beta', 'nip' => '2222222222']);
        $contract = Document::factory()->create([
            'name' => 'Umowa o pracę',
            'is_required' => true,
            'is_company_scoped' => true,
            'is_periodic' => false,
        ]);

        $inAlpha = Employee::factory()->create();
        $inBeta = Employee::factory()->create();

        CompanyAssignment::create([
            'employee_id' => $inAlpha->id,
            'company_id' => $alpha->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);
        CompanyAssignment::create([
            'employee_id' => $inBeta->id,
            'company_id' => $beta->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $this->assertFalse($inAlpha->hasAllDocumentsActiveInDateRange($start, $end));
        $this->assertFalse($inBeta->hasAllDocumentsActiveInDateRange($start, $end));

        $alphaMissing = $inAlpha->getAvailabilityStatus($start, $end)['missing_documents'];
        $this->assertTrue(collect($alphaMissing)->contains(
            fn ($row) => $row['document_id'] === $contract->id && str_contains($row['document_name'], 'Alpha')
        ));

        EmployeeDocument::factory()->create([
            'employee_id' => $inAlpha->id,
            'document_id' => $contract->id,
            'company_id' => $beta->id,
            'kind' => 'bezokresowy',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
        ]);

        $this->assertFalse($inAlpha->fresh()->hasAllDocumentsActiveInDateRange($start, $end));

        EmployeeDocument::factory()->create([
            'employee_id' => $inAlpha->id,
            'document_id' => $contract->id,
            'company_id' => $alpha->id,
            'kind' => 'bezokresowy',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
        ]);

        $this->assertTrue($inAlpha->fresh()->hasAllDocumentsActiveInDateRange($start, $end));
        $this->assertFalse($inBeta->fresh()->hasAllDocumentsActiveInDateRange($start, $end));

        Carbon::setTestNow();
    }

    public function test_company_scoped_requirement_skipped_without_company_assignment(): void
    {
        Carbon::setTestNow('2026-09-15');

        Document::factory()->create([
            'name' => 'Umowa o pracę',
            'is_required' => true,
            'is_company_scoped' => true,
        ]);

        $employee = Employee::factory()->create();
        $this->assertTrue($employee->hasAllDocumentsActiveInDateRange(now(), now()));

        Carbon::setTestNow();
    }

    public function test_global_required_document_still_applies_to_everyone(): void
    {
        Carbon::setTestNow('2026-09-15');

        Document::factory()->create([
            'name' => 'Dowód',
            'is_required' => true,
            'is_company_scoped' => false,
        ]);

        $employee = Employee::factory()->create();
        $this->assertFalse($employee->hasAllDocumentsActiveInDateRange(now(), now()));

        Carbon::setTestNow();
    }

    public function test_employee_document_stores_company_on_instance(): void
    {
        $alpha = Company::create(['name' => 'Alpha', 'nip' => '1111111111']);
        $contract = Document::factory()->create([
            'name' => 'Umowa o pracę',
            'is_required' => true,
            'is_company_scoped' => true,
            'is_periodic' => false,
        ]);
        $employee = Employee::factory()->create();

        $this->from(route('employee-documents.create', ['employee_id' => $employee->id]))
            ->post(route('employee-documents.store'), [
                'employee_id' => $employee->id,
                'document_id' => $contract->id,
                'valid_from' => '2026-01-01',
                'is_okresowy' => '0',
            ])
            ->assertSessionHasErrors('company_id');

        $this->post(route('employee-documents.store'), [
            'employee_id' => $employee->id,
            'document_id' => $contract->id,
            'company_id' => $alpha->id,
            'valid_from' => '2026-01-01',
            'is_okresowy' => '0',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'document_id' => $contract->id,
            'company_id' => $alpha->id,
        ]);
    }

    public function test_false_requirement_flags_stay_unchecked_on_edit_and_icon_save(): void
    {
        $document = Document::factory()->create([
            'name' => 'Uprawnienia piaskarza',
            'is_required' => false,
            'is_company_scoped' => false,
            'is_periodic' => false,
        ]);

        $html = $this->get(route('documents.edit', $document))->assertOk()->getContent();
        $this->assertStringNotContainsString('checked', $this->checkboxInput($html, 'is_required'));
        $this->assertStringNotContainsString('checked', $this->checkboxInput($html, 'is_company_scoped'));

        $this->put(route('documents.update', $document), [
            'name' => 'Uprawnienia piaskarza',
            'is_periodic' => '0',
            'planner_icon' => \App\Enums\DocumentPlannerIcon::IdCard->value,
        ])->assertRedirect(route('documents.index'));

        $fresh = $document->fresh();
        $this->assertFalse($fresh->is_required);
        $this->assertFalse($fresh->is_company_scoped);
        $this->assertSame(\App\Enums\DocumentPlannerIcon::IdCard->value, $fresh->planner_icon);
    }

    public function test_turning_off_company_scope_clears_company_on_existing_entries(): void
    {
        $alpha = Company::create(['name' => 'Alpha', 'nip' => '1111111111']);
        $document = Document::factory()->create([
            'name' => 'Szkolenie',
            'is_company_scoped' => true,
            'is_periodic' => false,
            'is_required' => false,
        ]);
        $entry = EmployeeDocument::factory()->create([
            'document_id' => $document->id,
            'company_id' => $alpha->id,
            'kind' => 'bezokresowy',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
        ]);

        $this->put(route('documents.update', $document), [
            'name' => 'Szkolenie',
            'is_periodic' => '0',
        ])->assertRedirect(route('documents.index'));

        $this->assertFalse($document->fresh()->is_company_scoped);
        $this->assertNull($entry->fresh()->company_id);
    }

    public function test_non_company_document_form_hides_company_and_drops_posted_company(): void
    {
        $alpha = Company::create(['name' => 'Alpha', 'nip' => '1111111111']);
        $idCard = Document::factory()->create([
            'name' => 'Dowód osobisty',
            'is_company_scoped' => false,
            'is_periodic' => false,
            'is_required' => true,
        ]);
        $employee = Employee::factory()->create();

        $html = $this->get(route('employee-documents.create', [
            'employee_id' => $employee->id,
            'document_id' => $idCard->id,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('id="company-field"', $html);
        $this->assertMatchesRegularExpression('/<div class="mb-3 d-none" id="company-field">/', $html);
        $this->assertMatchesRegularExpression('/<div class="col-md-6 d-none" id="valid-to-field">/', $html);
        $this->assertStringContainsString('Bez spółki', $html);

        $this->post(route('employee-documents.store'), [
            'employee_id' => $employee->id,
            'document_id' => $idCard->id,
            'company_id' => $alpha->id,
            'valid_from' => '2026-01-01',
            'valid_to' => '2027-01-01',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'document_id' => $idCard->id,
            'company_id' => null,
            'kind' => 'bezokresowy',
            'valid_to' => null,
        ]);
    }

    public function test_periodic_document_requires_valid_to_and_stores_kind(): void
    {
        $medical = Document::factory()->create([
            'name' => 'Badania',
            'is_company_scoped' => false,
            'is_periodic' => true,
            'is_required' => false,
        ]);
        $employee = Employee::factory()->create();

        $this->from(route('employee-documents.create', ['employee_id' => $employee->id]))
            ->post(route('employee-documents.store'), [
                'employee_id' => $employee->id,
                'document_id' => $medical->id,
                'valid_from' => '2026-01-01',
            ])
            ->assertSessionHasErrors('valid_to');

        $this->post(route('employee-documents.store'), [
            'employee_id' => $employee->id,
            'document_id' => $medical->id,
            'valid_from' => '2026-01-01',
            'valid_to' => '2027-01-01',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'document_id' => $medical->id,
            'kind' => 'okresowy',
            'valid_to' => '2027-01-01 00:00:00',
        ]);
    }

    private function checkboxInput(string $html, string $name): string
    {
        $matched = preg_match(
            '/<input\b[^>]*\bname="'.preg_quote($name, '/').'"[^>]*>/s',
            $html,
            $matches
        );

        $this->assertSame(1, $matched, "Missing checkbox {$name}");

        return $matches[0];
    }
}
