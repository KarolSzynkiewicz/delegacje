<?php

namespace Tests\Feature;

use App\Livewire\CostRegistryTable;
use App\Models\Company;
use App\Models\CostEntry;
use App\Models\FixedCostEntry;
use App\Models\Project;
use App\Models\ProjectVariableCost;
use App\Models\TransportCost;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleRepair;
use App\Services\VehicleRepairService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CostRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->admin = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->admin->assignRole($adminRole);
        }
    }

    public function test_index_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('cost-registry.index'))
            ->assertOk()
            ->assertSee('Rejestr kosztów');
    }

    public function test_each_source_type_gets_a_registry_entry_on_create(): void
    {
        $fixed = $this->fixedCost(['name' => 'Czynsz biurowy', 'accounting_date' => '2026-03-05']);
        $project = $this->projectCost(['name' => 'Rusztowania', 'incurred_date' => '2026-04-12']);
        $transport = $this->transportCost(['description' => null, 'cost_type' => 'fuel', 'cost_date' => '2026-05-20']);
        $repair = $this->repair(['price' => 1500, 'end_date' => '2026-06-30']);

        $this->assertDatabaseCount('cost_entries', 4);

        $this->assertDatabaseHas('cost_entries', [
            'source_type' => CostEntry::SOURCE_FIXED,
            'source_id' => $fixed->id,
            'name' => 'Czynsz biurowy',
            'period_year' => 2026,
            'period_month' => 3,
            'company_id' => null,
            'financially_irrelevant' => false,
        ]);
        $this->assertDatabaseHas('cost_entries', [
            'source_type' => CostEntry::SOURCE_PROJECT,
            'source_id' => $project->id,
            'name' => 'Rusztowania',
            'period_month' => 4,
        ]);
        $this->assertDatabaseHas('cost_entries', [
            'source_type' => CostEntry::SOURCE_TRANSPORT,
            'source_id' => $transport->id,
            'name' => 'Paliwo',
            'period_month' => 5,
        ]);
        $this->assertDatabaseHas('cost_entries', [
            'source_type' => CostEntry::SOURCE_REPAIR,
            'source_id' => $repair->id,
            'name' => 'Serwis '.$repair->vehicle->registration_number,
            'period_month' => 6,
        ]);
    }

    public function test_source_update_does_not_overwrite_registry_name_or_period(): void
    {
        $fixed = $this->fixedCost(['name' => 'Czynsz', 'accounting_date' => '2026-03-05']);
        $entry = $this->entryFor($fixed);
        $entry->update(['name' => 'Czynsz — biuro Gdańsk', 'period_month' => 4]);

        $fixed->update(['name' => 'Czynsz zmieniony', 'amount' => 2222, 'accounting_date' => '2026-08-01']);

        $entry->refresh();
        $this->assertSame('Czynsz — biuro Gdańsk', $entry->name);
        $this->assertSame(4, $entry->period_month);
        $this->assertSame(2222.0, $entry->amount());
    }

    public function test_repair_copy_in_fixed_costs_is_not_counted_twice(): void
    {
        $vehicle = Vehicle::factory()->create();
        $repair = app(VehicleRepairService::class)->startRepair($vehicle, [
            'action_type' => 'repair',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'price' => 900,
            'currency' => 'PLN',
        ]);

        $repair->refresh();
        $this->assertNotNull($repair->fixed_cost_entry_id, 'Serwis z ceną i datą końca tworzy kopię w kosztach stałych.');

        $this->assertDatabaseCount('cost_entries', 1);
        $this->assertDatabaseHas('cost_entries', [
            'source_type' => CostEntry::SOURCE_REPAIR,
            'source_id' => $repair->id,
        ]);
        $this->assertDatabaseMissing('cost_entries', [
            'source_type' => CostEntry::SOURCE_FIXED,
            'source_id' => $repair->fixed_cost_entry_id,
        ]);
    }

    public function test_repair_without_price_is_not_registered(): void
    {
        $this->repair(['price' => null, 'end_date' => null]);

        $this->assertDatabaseCount('cost_entries', 0);
    }

    public function test_deleting_source_removes_registry_entry(): void
    {
        $transport = $this->transportCost();
        $this->assertDatabaseCount('cost_entries', 1);

        $transport->delete();

        $this->assertDatabaseCount('cost_entries', 0);
    }

    public function test_totals_skip_irrelevant_entries_and_keep_currencies_apart(): void
    {
        $this->fixedCost(['name' => 'A', 'amount' => 100, 'currency' => 'PLN']);
        $this->projectCost(['name' => 'B', 'amount' => 50, 'currency' => 'PLN']);
        $this->transportCost(['amount' => 70, 'currency' => 'EUR']);
        $repair = $this->repair(['price' => 30, 'currency' => 'EUR']);

        $this->entryFor($repair)->update(['financially_irrelevant' => true]);

        Livewire::actingAs($this->admin)
            ->test(CostRegistryTable::class)
            ->assertViewHas('summary', fn (array $summary) => $summary['totals'] === ['EUR' => 70.0, 'PLN' => 150.0]
                && $summary['count'] === 4
                && $summary['irrelevant'] === 1
                && $summary['without_company'] === 4);
    }

    public function test_filters_by_company_including_none(): void
    {
        $company = Company::create(['name' => 'Alfa Sp. z o.o.', 'nip' => '1234567890']);
        $withCompany = $this->fixedCost(['name' => 'Koszt spółki Alfa']);
        $this->fixedCost(['name' => 'Koszt bez spółki']);

        $this->entryFor($withCompany)->update(['company_id' => $company->id]);

        Livewire::actingAs($this->admin)
            ->test(CostRegistryTable::class)
            ->set('companyFilter', (string) $company->id)
            ->assertSee('Koszt spółki Alfa')
            ->assertDontSee('Koszt bez spółki')
            ->set('companyFilter', CostRegistryTable::COMPANY_NONE)
            ->assertSee('Koszt bez spółki')
            ->assertDontSee('Koszt spółki Alfa');
    }

    public function test_filters_by_type_month_and_currency(): void
    {
        $this->fixedCost(['name' => 'Styczniowy PLN', 'accounting_date' => '2026-01-10', 'currency' => 'PLN']);
        $this->transportCost(['description' => 'Lutowy EUR', 'cost_date' => '2026-02-10', 'currency' => 'EUR']);

        $component = Livewire::actingAs($this->admin)->test(CostRegistryTable::class);

        $component->set('sourceTypeFilter', CostEntry::SOURCE_TRANSPORT)
            ->assertSee('Lutowy EUR')
            ->assertDontSee('Styczniowy PLN')
            ->set('sourceTypeFilter', '');

        $component->set('monthFilter', '1')
            ->assertSee('Styczniowy PLN')
            ->assertDontSee('Lutowy EUR')
            ->set('monthFilter', '');

        $component->set('currencyFilter', 'EUR')
            ->assertSee('Lutowy EUR')
            ->assertDontSee('Styczniowy PLN');
    }

    public function test_edit_saves_wrapper_fields_only(): void
    {
        $company = Company::create(['name' => 'Beta Sp. z o.o.', 'nip' => '9876543210']);
        $fixed = $this->fixedCost(['name' => 'Oryginalna nazwa', 'amount' => 123]);
        $entry = $this->entryFor($fixed);

        Livewire::actingAs($this->admin)
            ->test(CostRegistryTable::class)
            ->assertSet("rows.{$entry->id}.name", 'Oryginalna nazwa')
            ->set("rows.{$entry->id}.name", '  Nazwa z rejestru ')
            ->set("rows.{$entry->id}.company_id", (string) $company->id)
            ->set("rows.{$entry->id}.period", '2025-12')
            ->set("rows.{$entry->id}.comment", 'Przelew wewnątrz holdingu')
            ->call('toggleIrrelevant', $entry->id)
            ->assertHasNoErrors()
            ->assertDispatched('cost-registry-saved')
            ->assertSet("rows.{$entry->id}.name", 'Nazwa z rejestru');

        $entry->refresh();
        $this->assertSame('Nazwa z rejestru', $entry->name);
        $this->assertSame($company->id, $entry->company_id);
        $this->assertSame(2025, $entry->period_year);
        $this->assertSame(12, $entry->period_month);
        $this->assertTrue($entry->financially_irrelevant);
        $this->assertSame('Przelew wewnątrz holdingu', $entry->comment);

        $fixed->refresh();
        $this->assertSame('Oryginalna nazwa', $fixed->name);
        $this->assertSame(123.0, (float) $fixed->amount);
    }

    public function test_edit_requires_update_permission(): void
    {
        $viewer = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::create(['name' => 'podglad-kosztow', 'guard_name' => 'web']);
        $role->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'cost-registry.view', 'guard_name' => 'web'])
        );
        $viewer->assignRole($role);

        $entry = $this->entryFor($this->fixedCost());

        Livewire::actingAs($viewer)
            ->test(CostRegistryTable::class)
            ->assertSee('Koszt stały')
            ->call('toggleIrrelevant', $entry->id)
            ->assertForbidden();

        $this->assertFalse($entry->fresh()->financially_irrelevant);

        Livewire::actingAs($viewer)
            ->test(CostRegistryTable::class)
            ->set("rows.{$entry->id}.name", 'Próba zmiany')
            ->assertForbidden();

        $this->assertNotSame('Próba zmiany', $entry->fresh()->name);
    }

    public function test_inline_edit_rejects_empty_name_and_keeps_old_value(): void
    {
        $entry = $this->entryFor($this->fixedCost(['name' => 'Stała nazwa']));

        Livewire::actingAs($this->admin)
            ->test(CostRegistryTable::class)
            ->set("rows.{$entry->id}.name", '   ')
            ->assertHasErrors(["rows.{$entry->id}.name"]);

        $this->assertSame('Stała nazwa', $entry->fresh()->name);
    }

    public function test_create_many_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('cost-registry.create-many'))
            ->assertOk()
            ->assertSee('Dodaj wiele kosztów')
            ->assertSee('Domyślne dla nowych wierszy');
    }

    public function test_store_many_creates_fixed_costs_with_registry_fields(): void
    {
        $company = Company::create(['name' => 'Gamma Sp. z o.o.', 'nip' => '5555555555']);

        $this->actingAs($this->admin)
            ->post(route('cost-registry.store-many'), [
                'entries' => [
                    [
                        'accounting_date' => '2026-10-05',
                        'name' => 'Czynsz biuro',
                        'amount' => '4500',
                        'currency' => 'pln',
                        'company_id' => (string) $company->id,
                        'period' => '2026-10',
                        'category' => 'office',
                        'comment' => 'Faktura 10/2026',
                        'financially_irrelevant' => '0',
                    ],
                    [
                        'accounting_date' => '2026-10-07',
                        'name' => 'Przelew wewnętrzny',
                        'amount' => '12000.50',
                        'currency' => 'EUR',
                        'company_id' => '',
                        'period' => '2026-09',
                        'category' => '',
                        'comment' => '',
                        'financially_irrelevant' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('cost-registry.index'))
            ->assertSessionHas('success');

        $this->assertSame(2, FixedCostEntry::count());
        $this->assertSame(2, CostEntry::count());

        $first = FixedCostEntry::where('name', 'Czynsz biuro')->firstOrFail();
        $this->assertSame('PLN', $first->currency);
        $this->assertSame('office', $first->category);
        $this->assertSame('2026-10-01', $first->period_start->format('Y-m-d'));
        $this->assertSame('2026-10-31', $first->period_end->format('Y-m-d'));

        $firstEntry = $this->entryFor($first);
        $this->assertSame($company->id, $firstEntry->company_id);
        $this->assertSame(2026, $firstEntry->period_year);
        $this->assertSame(10, $firstEntry->period_month);
        $this->assertSame('Faktura 10/2026', $firstEntry->comment);
        $this->assertFalse($firstEntry->financially_irrelevant);

        $second = FixedCostEntry::where('name', 'Przelew wewnętrzny')->firstOrFail();
        $secondEntry = $this->entryFor($second);
        $this->assertNull($secondEntry->company_id);
        $this->assertSame(9, $secondEntry->period_month);
        $this->assertNull($secondEntry->comment);
        $this->assertTrue($secondEntry->financially_irrelevant);
    }

    public function test_store_many_validates_rows_and_creates_nothing(): void
    {
        $this->actingAs($this->admin)
            ->from(route('cost-registry.create-many'))
            ->post(route('cost-registry.store-many'), [
                'entries' => [
                    ['accounting_date' => '2026-10-05', 'name' => 'OK', 'amount' => '10', 'currency' => 'PLN', 'period' => '2026-10'],
                    ['accounting_date' => '2026-10-05', 'name' => '', 'amount' => '-1', 'currency' => 'PLN', 'period' => '2026-13'],
                ],
            ])
            ->assertRedirect(route('cost-registry.create-many'))
            ->assertSessionHasErrors(['entries.1.name', 'entries.1.amount', 'entries.1.period']);

        $this->assertSame(0, FixedCostEntry::count());
        $this->assertSame(0, CostEntry::count());
    }

    private function entryFor(\Illuminate\Database\Eloquent\Model $source): CostEntry
    {
        return CostEntry::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fixedCost(array $overrides = []): FixedCostEntry
    {
        return FixedCostEntry::create(array_merge([
            'name' => 'Koszt stały',
            'amount' => 1000,
            'currency' => 'PLN',
            'category' => 'office',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'accounting_date' => '2026-03-05',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function projectCost(array $overrides = []): ProjectVariableCost
    {
        return ProjectVariableCost::create(array_merge([
            'project_id' => Project::factory()->create()->id,
            'name' => 'Koszt projektowy',
            'amount' => 500,
            'currency' => 'PLN',
            'incurred_date' => '2026-04-12',
            'category' => 'material',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function transportCost(array $overrides = []): TransportCost
    {
        return TransportCost::create(array_merge([
            'cost_type' => 'fuel',
            'amount' => 300,
            'currency' => 'PLN',
            'cost_date' => '2026-05-20',
            'description' => 'Tankowanie',
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function repair(array $overrides = []): VehicleRepair
    {
        return VehicleRepair::create(array_merge([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'action_type' => 'repair',
            'start_date' => '2026-06-28',
            'end_date' => '2026-06-30',
            'price' => 1500,
            'currency' => 'PLN',
        ], $overrides));
    }
}
