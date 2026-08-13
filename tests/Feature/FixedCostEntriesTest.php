<?php

namespace Tests\Feature;

use App\Livewire\FixedCostEntriesTable;
use App\Models\FixedCostEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FixedCostEntriesTest extends TestCase
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

    public function test_index_opens_accounting_entries_tab(): void
    {
        $this->actingAs($this->user)
            ->get(route('fixed-costs.index'))
            ->assertOk()
            ->assertViewIs('fixed-costs.index')
            ->assertViewHas('activeTab', 'entries')
            ->assertSee('Koszty Księgowe');
    }

    public function test_entries_tab_appears_before_templates_tab(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('fixed-costs.tab.entries'))
            ->assertOk()
            ->assertViewHas('activeTab', 'entries')
            ->getContent();

        $entriesPos = strpos($html, 'Koszty Księgowe');
        $templatesPos = strpos($html, 'Szablony');

        $this->assertNotFalse($entriesPos);
        $this->assertNotFalse($templatesPos);
        $this->assertLessThan($templatesPos, $entriesPos);
    }

    public function test_templates_tab_is_still_available(): void
    {
        $this->actingAs($this->user)
            ->get(route('fixed-costs.tab.templates'))
            ->assertOk()
            ->assertViewHas('activeTab', 'templates')
            ->assertSee('Szablony Kosztów Ogólnofirmowych');
    }

    public function test_entries_table_shows_category_column(): void
    {
        $this->createEntry([
            'name' => 'Czynsz biurowy',
            'category' => 'office',
        ]);

        Livewire::actingAs($this->user)
            ->test(FixedCostEntriesTable::class)
            ->assertSee('Kategoria')
            ->assertSee('Czynsz biurowy')
            ->assertSee('Biuro (czynsze biurowe, media)');
    }

    public function test_search_filters_entries_by_name(): void
    {
        $this->createEntry(['name' => 'Czynsz biurowy', 'category' => 'office']);
        $this->createEntry(['name' => 'Licencja Adobe', 'category' => 'software']);

        Livewire::actingAs($this->user)
            ->test(FixedCostEntriesTable::class)
            ->set('search', 'Czynsz')
            ->assertSee('Czynsz biurowy')
            ->assertDontSee('Licencja Adobe');
    }

    public function test_category_filter_limits_results(): void
    {
        $this->createEntry(['name' => 'Czynsz biurowy', 'category' => 'office']);
        $this->createEntry(['name' => 'Licencja Adobe', 'category' => 'software']);

        Livewire::actingAs($this->user)
            ->test(FixedCostEntriesTable::class)
            ->set('categoryFilter', 'office')
            ->assertSee('Czynsz biurowy')
            ->assertDontSee('Licencja Adobe');
    }

    public function test_sort_by_name_orders_alphabetically(): void
    {
        $this->createEntry(['name' => 'Zebra koszt', 'category' => 'other']);
        $this->createEntry(['name' => 'Alfa koszt', 'category' => 'other']);

        Livewire::actingAs($this->user)
            ->test(FixedCostEntriesTable::class)
            ->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc')
            ->assertSeeInOrder(['Alfa koszt', 'Zebra koszt']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEntry(array $overrides = []): FixedCostEntry
    {
        return FixedCostEntry::create(array_merge([
            'name' => 'Koszt testowy',
            'amount' => 1000,
            'currency' => 'PLN',
            'category' => 'other',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'accounting_date' => '2026-08-05',
        ], $overrides));
    }
}
