<?php

namespace Tests\Feature;

use App\Livewire\AccommodationsTable;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccommodationsTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_default_list_hides_ended_rentals_and_keeps_owned_and_active(): void
    {
        $owned = Accommodation::factory()->create(['name' => 'Dom Wlasny Testowy']);
        $rented = $this->rentedAccommodation('Dom Wynajmowany Testowy', now()->addMonth());
        $ended = $this->endedAccommodation('Dom Po Najmie Testowy');

        Livewire::test(AccommodationsTable::class)
            ->assertSee('Dom Wlasny Testowy')
            ->assertSee('Dom Wynajmowany Testowy')
            ->assertDontSee('Dom Po Najmie Testowy')
            ->assertSee('W użyciu (własne + najem)');

        $this->assertSame('owned', $owned->fresh()->currentTenure());
        $this->assertSame('rented', $rented->fresh()->currentTenure());
        $this->assertSame('ended', $ended->fresh()->currentTenure());
    }

    public function test_tenure_filter_can_show_ended_or_only_owned(): void
    {
        Accommodation::factory()->create(['name' => 'Dom Wlasny Testowy']);
        $this->rentedAccommodation('Dom Wynajmowany Testowy', now()->addMonth());
        $this->endedAccommodation('Dom Po Najmie Testowy');

        Livewire::test(AccommodationsTable::class)
            ->set('tenureFilter', 'ended')
            ->assertSee('Dom Po Najmie Testowy')
            ->assertDontSee('Dom Wlasny Testowy')
            ->assertDontSee('Dom Wynajmowany Testowy')
            ->set('tenureFilter', 'owned')
            ->assertSee('Dom Wlasny Testowy')
            ->assertDontSee('Dom Po Najmie Testowy')
            ->assertDontSee('Dom Wynajmowany Testowy')
            ->set('tenureFilter', 'all')
            ->assertSee('Dom Wlasny Testowy')
            ->assertSee('Dom Wynajmowany Testowy')
            ->assertSee('Dom Po Najmie Testowy');
    }

    public function test_status_date_shows_tenure_and_occupancy_on_that_day(): void
    {
        $house = Accommodation::factory()->create([
            'name' => 'Dom Historyczny Testowy',
            'capacity' => 2,
        ]);
        $house->leases()->create([
            'type' => 'wynajmowany',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);
        $house->assignments()->create([
            'employee_id' => Employee::factory()->create()->id,
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ]);

        Livewire::test(AccommodationsTable::class)
            ->set('statusDate', '2026-02-15')
            ->assertSee('Dom Historyczny Testowy')
            ->assertSee('1 / 2')
            ->set('statusDate', '2026-06-01')
            ->assertDontSee('Dom Historyczny Testowy')
            ->set('tenureFilter', 'ended')
            ->assertSee('Dom Historyczny Testowy')
            ->assertSee('Poza użytkiem');
    }

    public function test_clear_filters_restores_default_current_tenure(): void
    {
        $this->endedAccommodation('Dom Po Najmie Testowy');
        Accommodation::factory()->create(['name' => 'Dom Wlasny Testowy']);

        Livewire::test(AccommodationsTable::class)
            ->set('tenureFilter', 'all')
            ->set('statusDate', '2026-01-01')
            ->assertSee('Dom Po Najmie Testowy')
            ->call('clearFilters')
            ->assertSet('tenureFilter', 'current')
            ->assertSet('statusDate', '')
            ->assertDontSee('Dom Po Najmie Testowy')
            ->assertSee('Dom Wlasny Testowy');
    }

    private function rentedAccommodation(string $name, $endDate): Accommodation
    {
        $accommodation = Accommodation::factory()->create(['name' => $name]);
        $accommodation->leases()->create([
            'type' => 'wynajmowany',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        return $accommodation;
    }

    private function endedAccommodation(string $name): Accommodation
    {
        $accommodation = Accommodation::factory()->create(['name' => $name]);
        $accommodation->leases()->create([
            'type' => 'wynajmowany',
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        return $accommodation;
    }
}
