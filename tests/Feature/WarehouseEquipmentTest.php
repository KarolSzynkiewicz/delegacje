<?php

namespace Tests\Feature;

use App\Enums\LocationPurposeType;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Enums\TaskStatus;
use App\Livewire\EmployeeEquipmentHistory;
use App\Livewire\EquipmentForm;
use App\Livewire\EquipmentIssueHistory;
use App\Livewire\EquipmentStockMovementForm;
use App\Livewire\EquipmentStockTimeline;
use App\Livewire\LogisticsEventWarehouseTransfers;
use App\Livewire\WarehouseConsumeForm;
use App\Livewire\WarehouseIssueForm;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentStock;
use App\Models\EquipmentStockMovement;
use App\Models\EquipmentVariant;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\WarehouseDispatch;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class WarehouseEquipmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }

        $this->warehouse = app(WarehouseService::class)->default();
    }

    public function test_menu_uses_warehouse_labels(): void
    {
        $this->actingAs($this->user)
            ->get(route('warehouses.index'))
            ->assertRedirect(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id]));

        $later = Warehouse::factory()->create(['name' => 'Późniejszy magazyn']);
        $this->assertGreaterThan($this->warehouse->id, $later->id);

        $this->actingAs($this->user)
            ->get(route('warehouses.index'))
            ->assertRedirect(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id]));

        $this->actingAs($this->user)
            ->get(route('equipment.index'))
            ->assertOk()
            ->assertSee('Magazyn')
            ->assertSee('Magazyn — '.$this->warehouse->name)
            ->assertSee('Dodaj do magazynu')
            ->assertSee('Asortyment')
            ->assertSee('Zleć wydanie')
            ->assertSee('Zlecenia')
            ->assertSee('Wydane')
            ->assertSee('eq-wh-card is-active', false)
            ->assertSee('data-warehouse-id="'.$this->warehouse->id.'"', false)
            ->assertDontSee('Stan magazynu')
            ->assertDontSee('Asortyment historyczny');
    }

    public function test_issue_and_consume_are_full_pages_from_warehouse_tabs(): void
    {
        $this->actingAs($this->user)
            ->get(route('equipment.tab.issues'))
            ->assertOk()
            ->assertSee('Asortyment')
            ->assertDontSee('Stan magazynu')
            ->assertDontSee('Asortyment historyczny')
            ->assertSee('Wydane')
            ->assertDontSee('Wydaj bezzwrotnie')
            ->assertSee('Rozchód');

        $this->actingAs($this->user)
            ->get(route('equipment-issues.create', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Zlecenie wydania')
            ->assertSee('Wydanie dla')
            ->assertSee('Data wydania')
            ->assertDontSee('Na stanie')
            ->assertDontSee('Co ma dostać')
            ->assertDontSee('Przypisanie do projektu')
            ->assertSee('Asortyment')
            ->assertSee('Do wydania')
            ->assertDontSee('nazwa wariantu')
            ->assertDontSee('Do wydania bezzwrotnie')
            ->assertDontSee('Niezwracalny');

        $this->actingAs($this->user)
            ->get(route('equipment-consumptions.create', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Rozchód');
    }

    public function test_can_create_type_with_kinds_via_livewire(): void
    {
        $this->actingAs($this->user)
            ->get(route('equipment.create', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertDontSee('Ilość w tym magazynie')
            ->assertSee('Minimalna ilość');

        Livewire::actingAs($this->user)
            ->test(EquipmentForm::class, ['warehouse' => $this->warehouse])
            ->set('name', 'Spodnie BHP')
            ->set('category', 'Odzież BHP')
            ->set('has_variants', true)
            ->set('variant_label', 'Rozmiar')
            ->set('issuable', true)
            ->set('returnable', true)
            ->set('variants', [
                ['id' => null, 'value' => 'M', 'min_quantity' => 2],
                ['id' => null, 'value' => 'L', 'min_quantity' => 1],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $equipment = Equipment::query()->where('name', 'Spodnie BHP')->first();
        $this->assertNotNull($equipment);
        $this->assertTrue($equipment->issuable);
        $this->assertTrue($equipment->returnable);
        $this->assertSame('Rozmiar', $equipment->variant_label);
        $this->assertCount(2, $equipment->variants);
        $this->assertSame(0, $equipment->variants->firstWhere('value', 'M')->quantityIn($this->warehouse));
        $this->assertSame(2, $equipment->variants->firstWhere('value', 'M')->minQuantityIn($this->warehouse));
        $this->assertSame(0, EquipmentStockMovement::query()->count());
    }

    public function test_can_create_non_issuable_item_without_variants(): void
    {
        Livewire::actingAs($this->user)
            ->test(EquipmentForm::class, ['warehouse' => $this->warehouse])
            ->set('name', 'Opony zamienne')
            ->set('category', 'Części')
            ->set('has_variants', false)
            ->set('issuable', false)
            ->set('returnable', true)
            ->set('variants', [
                ['id' => null, 'value' => '', 'min_quantity' => 1],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $equipment = Equipment::query()->where('name', 'Opony zamienne')->first();
        $this->assertNotNull($equipment);
        $this->assertFalse($equipment->issuable);
        $this->assertFalse($equipment->returnable);
        $this->assertNull($equipment->variant_label);
        $this->assertCount(1, $equipment->variants);
        $this->assertNull($equipment->variants->first()->value);
        $this->assertSame(0, $equipment->variants->first()->quantityIn($this->warehouse));

        $this->actingAs($this->user)
            ->get(route('equipment.index'))
            ->assertOk()
            ->assertSee('Opony zamienne')
            ->assertSee('Inny asortyment')
            ->assertDontSee('Niezwracalny');
    }

    public function test_warehouse_index_shows_kind_column_and_labels(): void
    {
        $equipment = Equipment::factory()->create([
            'name' => 'Okulary',
            'description' => 'Przeciwsłoneczne UV',
            'variant_label' => 'Filtr',
        ]);
        EquipmentVariant::factory()->inStock(6, 1)->create([
            'equipment_id' => $equipment->id,
            'value' => 'UV400',
        ]);

        $this->actingAs($this->user)
            ->get(route('equipment.index'))
            ->assertOk()
            ->assertSee('Typ / rodzaj')
            ->assertSee('W magazynie')
            ->assertSee('Zarezerwowane')
            ->assertSee('W innych magazynach')
            ->assertSee('Do zwrotu tutaj')
            ->assertSee('Do zwrotu w innych magazynach')
            ->assertSee('Okulary')
            ->assertSee('Przeciwsłoneczne UV')
            ->assertSee('Filtr')
            ->assertSee('UV400')
            ->assertSee('data-eq-stock-toggle="'.$equipment->id.'"', false)
            ->assertSee('data-eq-stock-parent="'.$equipment->id.'"', false)
            ->assertSee('Asortyment dla pracowników')
            ->assertSee('Zwracalny');
    }

    public function test_stock_tab_groups_items_by_employee_and_other_assortment(): void
    {
        Equipment::factory()->create(['name' => 'Spodnie BHP']);
        Equipment::factory()->notReturnable()->withoutKinds()->create(['name' => 'Rękawice']);
        Equipment::factory()->notIssuable()->withoutKinds()->create(['name' => 'Opony zamienne']);

        $html = $this->actingAs($this->user)
            ->get(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSeeInOrder([
                'Asortyment dla pracowników',
                'Zwracalny',
                'Spodnie BHP',
                'Niezwracalny',
                'Rękawice',
                'Inny asortyment',
                'Opony zamienne',
            ]);
    }

    public function test_orders_tab_lists_pending_issue_orders(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
        ]);
        $equipment = Equipment::factory()->create(['name' => 'Kask BHP']);
        $variant = EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);
        $dispatch = WarehouseDispatch::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->user->id,
            'status' => WarehouseDispatch::STATUS_RESERVED,
            'number' => 'ZW-'.now()->year.'-0007',
            'year' => (int) now()->year,
            'sequence' => 7,
        ]);
        EquipmentIssue::factory()->create([
            'equipment_id' => $equipment->id,
            'equipment_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'warehouse_dispatch_id' => $dispatch->id,
            'employee_id' => $employee->id,
            'quantity_issued' => 1,
            'status' => EquipmentIssue::STATUS_RESERVED,
            'issued_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('equipment.tab.orders', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Zlecenia')
            ->assertSee($dispatch->number)
            ->assertSee('Anna Nowak')
            ->assertSee('Kompletuj')
            ->assertSee(route('warehouse-dispatches.show', $dispatch), false);

        $this->actingAs($this->user)
            ->get(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertDontSee($dispatch->number);
    }

    public function test_orders_tab_shows_empty_state_when_nothing_is_waiting(): void
    {
        $this->actingAs($this->user)
            ->get(route('equipment.tab.orders', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Nic nie czeka na wydanie w tym magazynie.')
            ->assertSee('empty-state', false)
            ->assertSee('Zleć wydanie')
            ->assertSee(route('equipment-issues.create', ['warehouse_id' => $this->warehouse->id]), false);
    }

    public function test_can_add_warehouse_for_location_and_see_separate_stock(): void
    {
        $location = Location::factory()->create(['name' => 'Warsztat Gdańsk']);
        $equipment = Equipment::factory()->withoutKinds()->create(['name' => 'Młotek']);
        $variant = EquipmentVariant::factory()->unnamed()->inStock(8, 1, $this->warehouse)->create([
            'equipment_id' => $equipment->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('warehouses.store'), [
                'location_id' => $location->id,
                'name' => 'Magazyn Gdańsk',
            ])
            ->assertRedirect();

        $other = Warehouse::query()->where('location_id', $location->id)->first();
        $this->assertNotNull($other);
        $this->assertTrue($location->fresh()->hasPurpose(LocationPurposeType::WAREHOUSE));

        EquipmentStock::query()->create([
            'warehouse_id' => $other->id,
            'equipment_variant_id' => $variant->id,
            'quantity_in_stock' => 2,
            'min_quantity' => 1,
        ]);

        $this->actingAs($this->user)
            ->get(route('equipment.index', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Młotek')
            ->assertSee('Magazyn Gdańsk');

        $this->actingAs($this->user)
            ->get(route('equipment.index', ['warehouse_id' => $other->id]))
            ->assertOk()
            ->assertSee('Młotek')
            ->assertSee('Magazyn — Magazyn Gdańsk')
            ->assertSee('data-warehouse-id="'.$other->id.'" aria-current="page"', false)
            ->assertSee('Warsztat Gdańsk')
            ->assertSee('1 poz.');

        $this->assertSame(8, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(2, $variant->fresh()->quantityIn($other));
        $this->assertSame(2, $variant->fresh()->load('stocks')->quantityInOthers($this->warehouse));
        $this->assertSame(8, $variant->fresh()->load('stocks')->quantityInOthers($other));
        $this->assertSame(0, $variant->fresh()->issuedOutstandingIn($this->warehouse));
        $this->assertSame(0, $variant->fresh()->issuedOutstandingInOthers($this->warehouse));
    }

    public function test_issues_tab_shows_outbound_history_from_all_warehouses(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
        ]);
        $equipment = Equipment::factory()->create(['name' => 'Kask BHP']);
        $variant = EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);
        $dispatch = WarehouseDispatch::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->user->id,
            'number' => 'WZ-'.now()->year.'-0001',
            'year' => (int) now()->year,
            'sequence' => 1,
        ]);
        EquipmentIssue::factory()->create([
            'equipment_id' => $equipment->id,
            'equipment_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'warehouse_dispatch_id' => $dispatch->id,
            'employee_id' => $employee->id,
            'quantity_issued' => 2,
            'status' => 'issued',
            'issued_by' => $this->user->id,
        ]);

        $location = Location::factory()->create(['name' => 'Warsztat Gdańsk']);
        $this->actingAs($this->user)
            ->post(route('warehouses.store'), [
                'location_id' => $location->id,
                'name' => 'Magazyn Gdańsk',
            ])
            ->assertRedirect();
        $other = Warehouse::query()->where('location_id', $location->id)->firstOrFail();
        EquipmentIssue::factory()->create([
            'equipment_id' => $equipment->id,
            'equipment_variant_id' => $variant->id,
            'warehouse_id' => $other->id,
            'employee_id' => $employee->id,
            'quantity_issued' => 1,
            'status' => 'issued',
            'issued_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('equipment.tab.issues'))
            ->assertOk()
            ->assertSee('Kask BHP')
            ->assertSee('Wydanie do zwrotu')
            ->assertSee('Magazyn')
            ->assertSee('Do zwrotu')
            ->assertSee('Nowak')
            ->assertSee('Magazyn Gdańsk')
            ->assertSee($dispatch->number)
            ->assertSee(route('warehouse-dispatches.show', $dispatch), false)
            ->assertSee('data-warehouse-id="*"', false)
            ->assertSee('eq-wh-card is-active', false)
            ->assertSee('Wszystkie magazyny')
            ->assertSee('data-warehouse-id="'.$this->warehouse->id.'"', false)
            ->assertSee('data-warehouse-id="'.$other->id.'"', false);
    }

    public function test_non_issuable_items_are_hidden_from_issue_form(): void
    {
        $tires = Equipment::factory()->notIssuable()->withoutKinds()->create(['name' => 'Opony zamienne']);
        EquipmentVariant::factory()->unnamed()->inStock(4)->create([
            'equipment_id' => $tires->id,
        ]);
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP', 'variant_label' => 'Rozmiar']);
        EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);
        $gloves = Equipment::factory()->notReturnable()->withoutKinds()->create(['name' => 'Rękawice']);
        EquipmentVariant::factory()->unnamed()->inStock(10)->create([
            'equipment_id' => $gloves->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->assertSee('Spodnie BHP')
            ->assertSee('Rękawice')
            ->assertDontSee('Opony zamienne');
    }

    public function test_permanent_issue_decrements_stock_and_is_not_outstanding(): void
    {
        $employee = Employee::factory()->create();
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP', 'variant_label' => 'Rozmiar']);
        EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);
        $gloves = Equipment::factory()->notReturnable()->withoutKinds()->create(['name' => 'Rękawice']);
        $variant = EquipmentVariant::factory()->unnamed()->inStock(10, 0, $this->warehouse)->create([
            'equipment_id' => $gloves->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->assertSee('Rękawice')
            ->assertSee('Spodnie BHP')
            ->set('employeeIds', [$employee->id])
            ->call('addToCart', $variant->id, 'given', 3)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipment.tab.orders', ['warehouse_id' => $this->warehouse->id]));

        $issue = EquipmentIssue::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($issue);
        $this->assertSame(EquipmentIssue::STATUS_RESERVED, $issue->status);
        $this->assertSame(10, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(7, $variant->fresh()->availableIn($this->warehouse));
        $this->assertSame(3, $variant->fresh()->reservedIn($this->warehouse));
        $this->assertSame(0, $variant->fresh()->issuedOutstandingIn($this->warehouse));

        $this->actingAs($this->user)
            ->get(route('equipment.tab.orders', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee($this->latestDispatch()->number)
            ->assertSee('Kompletuj');

        $this->fulfillDispatch($this->latestDispatch())
            ->assertRedirect(route('warehouse-dispatches.show', $this->latestDispatch()));

        $issue->refresh();
        $this->assertSame(EquipmentIssue::STATUS_GIVEN, $issue->status);
        $this->assertSame(7, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(7, $variant->fresh()->availableIn($this->warehouse));
        $this->assertSame(0, $variant->fresh()->reservedIn($this->warehouse));
        $this->assertSame(0, $variant->fresh()->issuedOutstandingIn($this->warehouse));

        $this->actingAs($this->user)
            ->get(route('equipment.tab.issues'))
            ->assertOk()
            ->assertSee('Wydanie bezzwrotne')
            ->assertSee('Bezzwrotne')
            ->assertDontSee('Zwróć/Zgłoś');
    }

    public function test_confirming_issue_order_creates_task_linked_to_dispatch(): void
    {
        $assignee = User::factory()->create(['name' => 'Magazynier Testowy']);
        $employee = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $gloves = Equipment::factory()->notReturnable()->withoutKinds()->create(['name' => 'Rękawice']);
        $variant = EquipmentVariant::factory()->unnamed()->inStock(10, 0, $this->warehouse)->create([
            'equipment_id' => $gloves->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->set('employeeIds', [$employee->id])
            ->call('addToCart', $variant->id, 'given', 2)
            ->set('notes', 'Daj im rękawice')
            ->set('assigneeId', $assignee->id)
            ->call('prepare')
            ->assertSet('confirming', true)
            ->assertSee('Przypisz kompletację')
            ->assertSee('Magazynier Testowy')
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipment.tab.orders', ['warehouse_id' => $this->warehouse->id]));

        $dispatch = $this->latestDispatch();
        $task = ProjectTask::query()
            ->where('subject_type', $dispatch->getMorphClass())
            ->where('subject_id', $dispatch->id)
            ->first();

        $this->assertNotNull($task);
        $this->assertSame('Kompletacja '.$dispatch->number, $task->name);
        $this->assertSame('Magazyn', $task->category);
        $this->assertSame($assignee->id, $task->assigned_to);
        $this->assertSame(TaskStatus::PENDING, $task->status);
        $this->assertStringContainsString('Anna Nowak', $task->description);
        $this->assertStringContainsString('Rękawice', $task->description);
        $this->assertStringContainsString(route('warehouse-dispatches.show', $dispatch), $task->description);

        $card = $task->sourceCard();
        $this->assertSame(route('warehouse-dispatches.show', $dispatch), $card['url']);
        $this->assertSame('Dokument '.$dispatch->number, $card['label']);

        $this->actingAs($this->user)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Dokument '.$dispatch->number)
            ->assertSee(route('warehouse-dispatches.show', $dispatch), false);

        $this->actingAs($this->user)
            ->get(route('warehouse-dispatches.show', $dispatch))
            ->assertOk()
            ->assertSee('Kompletacja '.$dispatch->number)
            ->assertSee('Magazynier Testowy');

        $this->fulfillDispatch($dispatch)
            ->assertRedirect(route('warehouse-dispatches.show', $dispatch));

        $this->assertSame(TaskStatus::COMPLETED, $task->fresh()->status);
    }

    public function test_can_issue_multiple_items_to_one_person(): void
    {
        $employee = Employee::factory()->create();
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP', 'variant_label' => 'Rozmiar']);
        $sizeM = EquipmentVariant::factory()->inStock(10)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);
        $glasses = Equipment::factory()->create(['name' => 'Okulary', 'variant_label' => 'Filtr']);
        $uv = EquipmentVariant::factory()->inStock(8)->create([
            'equipment_id' => $glasses->id,
            'value' => 'UV400',
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->set('employeeIds', [$employee->id])
            ->call('addToCart', $sizeM->id, 'returnable', 2)
            ->call('addToCart', $uv->id, 'returnable', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipment.tab.orders', ['warehouse_id' => $this->warehouse->id]));

        $this->assertSame(2, EquipmentIssue::query()->where('employee_id', $employee->id)->count());
        $this->assertSame(10, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(8, $uv->fresh()->quantityIn($this->warehouse));
        $this->assertSame(8, $sizeM->fresh()->availableIn($this->warehouse));
        $this->assertSame(7, $uv->fresh()->availableIn($this->warehouse));
        $this->assertTrue(
            EquipmentIssue::query()->where('employee_id', $employee->id)->pluck('warehouse_id')->every(
                fn ($id) => (int) $id === $this->warehouse->id
            )
        );
        $batchIds = EquipmentIssue::query()->where('employee_id', $employee->id)->pluck('batch_id')->unique();
        $this->assertCount(1, $batchIds);
        $this->assertNotNull($batchIds->first());
        $dispatchIds = EquipmentIssue::query()->where('employee_id', $employee->id)->pluck('warehouse_dispatch_id')->unique();
        $this->assertCount(1, $dispatchIds);
        $this->assertNotNull($dispatchIds->first());
    }

    public function test_cart_decreases_remaining_stock_preview(): void
    {
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP', 'variant_label' => 'Rozmiar']);
        $sizeM = EquipmentVariant::factory()->inStock(10)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->set('employeeIds', [Employee::factory()->create()->id])
            ->call('addToCart', $sizeM->id, 'returnable', 3);

        $this->assertSame(7, $component->instance()->remainingFor($sizeM->id));
        $component->assertSee('7 / 10');
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $pants = Equipment::factory()->create();
        $sizeM = EquipmentVariant::factory()->inStock(2)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->set('employeeIds', [Employee::factory()->create()->id])
            ->call('addToCart', $sizeM->id, 'returnable', 5)
            ->assertHasErrors('lines');
    }

    public function test_issue_form_uses_employee_picker_and_has_no_save_and_next(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->assertSee('Wydanie dla')
            ->assertSee('Imię lub nazwisko')
            ->assertSee('Anna Nowak')
            ->assertDontSee('Wydaj i następna osoba')
            ->dispatch('employees-updated', employeeIds: [$anna->id])
            ->assertSet('employeeIds', [$anna->id])
            ->call('onEmployeesUpdated', [$anna->id])
            ->assertSet('employeeIds', [$anna->id]);
    }

    public function test_variant_label_is_required_only_when_item_has_variants(): void
    {
        Livewire::actingAs($this->user)
            ->test(EquipmentForm::class, ['warehouse' => $this->warehouse])
            ->set('name', 'Młotek')
            ->set('has_variants', true)
            ->set('variant_label', '')
            ->set('variants', [
                ['id' => null, 'value' => 'A', 'quantity_in_stock' => 1, 'min_quantity' => 0],
            ])
            ->call('save')
            ->assertHasErrors('variant_label');

        Livewire::actingAs($this->user)
            ->test(EquipmentForm::class, ['warehouse' => $this->warehouse])
            ->set('name', 'Młotek')
            ->set('has_variants', false)
            ->set('variant_label', '')
            ->set('variants', [
                ['id' => null, 'value' => '', 'quantity_in_stock' => 3, 'min_quantity' => 0],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();
    }

    public function test_can_consume_non_issuable_stock_without_open_issue(): void
    {
        $employee = Employee::factory()->create();
        $tires = Equipment::factory()->notIssuable()->withoutKinds()->create(['name' => 'Opony zamienne']);
        $variant = EquipmentVariant::factory()->unnamed()->inStock(4, 1, $this->warehouse)->create([
            'equipment_id' => $tires->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseConsumeForm::class, ['warehouse' => $this->warehouse])
            ->set('destinationType', 'employee')
            ->set('destinationId', $employee->id)
            ->set('notes', 'do busa')
            ->set('addEquipmentId', $tires->id)
            ->set('addVariantId', $variant->id)
            ->set('addQuantity', 2)
            ->call('addLine')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipment.tab.issues'));

        $this->assertSame(2, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(0, EquipmentIssue::query()->count());
        $movement = \App\Models\EquipmentStockMovement::query()->first();
        $this->assertSame(1, \App\Models\EquipmentStockMovement::query()->count());
        $this->assertSame($employee->id, $movement->employee_id);
        $this->assertSame('employee', $movement->consumed_for_type);
        $this->assertSame($employee->id, $movement->consumed_for_id);
    }

    public function test_consume_form_does_not_list_issuable_items(): void
    {
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseConsumeForm::class, ['warehouse' => $this->warehouse])
            ->set('equipmentSearch', 'Spodnie')
            ->assertDontSee('Spodnie BHP');
    }

    public function test_product_page_shows_consume_only_for_non_issuable_and_saves_destination(): void
    {
        $issuable = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        EquipmentVariant::factory()->inStock(4)->create([
            'equipment_id' => $issuable->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $issuable,
                'warehouse' => $this->warehouse,
            ])
            ->assertSee('Wydaj')
            ->assertDontSee('Rozchód')
            ->call('startConsume')
            ->assertSet('action', '');

        $tires = Equipment::factory()->notIssuable()->withoutKinds()->create(['name' => 'Opony zamienne']);
        $variant = EquipmentVariant::factory()->unnamed()->inStock(6, 0, $this->warehouse)->create([
            'equipment_id' => $tires->id,
        ]);
        $vehicle = Vehicle::factory()->create([
            'registration_number' => 'WZ 1234',
            'brand' => 'Ford',
            'model' => 'Transit',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $tires,
                'warehouse' => $this->warehouse,
            ])
            ->assertSee('Rozchód')
            ->assertDontSee('Wydaj')
            ->call('startConsume')
            ->assertSet('action', 'consume')
            ->assertSee('Przeznaczenie')
            ->set('destinationType', 'vehicle')
            ->set('destinationSearch', 'WZ 1234')
            ->assertSee('WZ 1234')
            ->call('selectDestination', $vehicle->id)
            ->set('lines', [
                ['variant_id' => $variant->id, 'quantity' => 2],
            ])
            ->set('notes', 'wymiana na busie')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(4, $variant->fresh()->quantityIn($this->warehouse));
        $movement = EquipmentStockMovement::query()->first();
        $this->assertSame(StockMovementType::CONSUMPTION, $movement->type);
        $this->assertSame('vehicle', $movement->consumed_for_type);
        $this->assertSame($vehicle->id, $movement->consumed_for_id);
        $this->assertNull($movement->employee_id);
        $this->assertSame('Auto · WZ 1234 — Ford Transit', $movement->destinationMeta());
    }

    public function test_consume_can_target_a_project(): void
    {
        $oil = Equipment::factory()->notIssuable()->withoutKinds()->create(['name' => 'Olej silnikowy']);
        $variant = EquipmentVariant::factory()->unnamed()->inStock(3, 0, $this->warehouse)->create([
            'equipment_id' => $oil->id,
        ]);
        $project = Project::factory()->create(['name' => 'Budowa Gdańsk']);

        Livewire::actingAs($this->user)
            ->test(WarehouseConsumeForm::class, ['warehouse' => $this->warehouse])
            ->set('destinationType', 'project')
            ->call('selectDestination', $project->id)
            ->set('addEquipmentId', $oil->id)
            ->set('addVariantId', $variant->id)
            ->set('addQuantity', 1)
            ->call('addLine')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $movement = EquipmentStockMovement::query()->first();
        $this->assertSame('project', $movement->consumed_for_type);
        $this->assertSame($project->id, $movement->consumed_for_id);
        $this->assertSame('Projekt · Budowa Gdańsk', $movement->destinationMeta());
    }

    public function test_destination_show_pages_list_warehouse_consumptions(): void
    {
        $oil = Equipment::factory()->notIssuable()->withoutKinds()->create(['name' => 'Olej silnikowy']);
        $variant = EquipmentVariant::factory()->unnamed()->inStock(10, 0, $this->warehouse)->create([
            'equipment_id' => $oil->id,
        ]);
        $vehicle = Vehicle::factory()->create([
            'registration_number' => 'WZ 1234',
            'brand' => 'Ford',
            'model' => 'Transit',
        ]);
        $home = Accommodation::factory()->create(['name' => 'Dom Gdańsk']);
        $project = Project::factory()->create(['name' => 'Budowa Gdańsk']);

        $this->actingAs($this->user);
        $service = app(EquipmentService::class);
        $service->consumeItems([['variant_id' => $variant->id, 'quantity' => 2]], $this->warehouse, $vehicle, 'wymiana oleju');
        $service->consumeItems([['variant_id' => $variant->id, 'quantity' => 1]], $this->warehouse, $home, 'do kotła');
        $service->consumeItems([['variant_id' => $variant->id, 'quantity' => 3]], $this->warehouse, $project, 'na budowę');

        $this->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Rozchód z magazynu')
            ->assertSee('Olej silnikowy')
            ->assertSee('wymiana oleju')
            ->assertDontSee('do kotła');

        $this->get(route('accommodations.show', $home))
            ->assertOk()
            ->assertSee('Rozchód z magazynu')
            ->assertSee('Olej silnikowy')
            ->assertSee('do kotła')
            ->assertDontSee('wymiana oleju');

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Rozchód z magazynu')
            ->assertSee('Olej silnikowy')
            ->assertSee('na budowę')
            ->assertDontSee('wymiana oleju');
    }

    public function test_can_rename_warehouse(): void
    {
        $this->actingAs($this->user)
            ->put(route('warehouses.update', $this->warehouse), [
                'name' => 'Siedziba HQ',
                'is_default' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('Siedziba HQ', $this->warehouse->fresh()->name);
        $this->assertTrue($this->warehouse->fresh()->is_default);
    }

    public function test_cannot_delete_default_warehouse(): void
    {
        $this->actingAs($this->user)
            ->from(route('warehouses.edit', $this->warehouse))
            ->delete(route('warehouses.destroy', $this->warehouse))
            ->assertRedirect();

        $this->assertDatabaseHas('warehouses', ['id' => $this->warehouse->id]);
    }

    public function test_trashing_equipment_moves_it_to_historical_assortment(): void
    {
        $item = Equipment::factory()->withoutKinds()->create(['name' => 'Młotek']);
        EquipmentVariant::factory()->unnamed()->inStock(3)->create([
            'equipment_id' => $item->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('equipment.destroy', ['equipment' => $item, 'warehouse_id' => $this->warehouse->id]))
            ->assertRedirect(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id, 'withdrawn' => 1]))
            ->assertSessionHas('success');

        $item->refresh();
        $this->assertTrue($item->is_archived);
        $this->assertNotNull($item->removed_at);
        $this->assertSame(3, $item->variants()->first()->quantityIn($this->warehouse));

        $this->actingAs($this->user)
            ->get(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertDontSee('Młotek')
            ->assertSee('Pokaż wycofane');

        $this->actingAs($this->user)
            ->get(route('equipment.tab.archived', ['warehouse_id' => $this->warehouse->id]))
            ->assertRedirect(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id, 'withdrawn' => 1]));

        $this->actingAs($this->user)
            ->get(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id, 'withdrawn' => 1]))
            ->assertOk()
            ->assertSee('Wycofane')
            ->assertSee('Młotek');
    }

    public function test_archived_equipment_can_be_restored_to_stock_tab(): void
    {
        $item = Equipment::factory()->archived()->withoutKinds()->create(['name' => 'Kask BHP']);
        EquipmentVariant::factory()->unnamed()->inStock(2)->create([
            'equipment_id' => $item->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('equipment.restore', ['equipment' => $item, 'warehouse_id' => $this->warehouse->id]))
            ->assertRedirect(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id]))
            ->assertSessionHas('success');

        $item->refresh();
        $this->assertFalse($item->is_archived);
        $this->assertNull($item->removed_at);

        $this->actingAs($this->user)
            ->get(route('equipment.tab.stock', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Kask BHP');
    }

    public function test_issue_form_hides_archived_equipment(): void
    {
        $item = Equipment::factory()->archived()->create(['name' => 'Stare spodnie', 'variant_label' => 'Rozmiar']);
        EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $item->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->assertDontSee('Stare spodnie');
    }

    public function test_issue_form_groups_variants_behind_dropdown(): void
    {
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP', 'variant_label' => 'Rozmiar']);
        EquipmentVariant::factory()->inStock(7)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);
        EquipmentVariant::factory()->inStock(3)->create([
            'equipment_id' => $pants->id,
            'value' => 'L',
        ]);
        $gloves = Equipment::factory()->notReturnable()->withoutKinds()->create(['name' => 'Rękawice']);
        EquipmentVariant::factory()->unnamed()->inStock(12)->create([
            'equipment_id' => $gloves->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->assertSee('Spodnie BHP')
            ->assertSee('10 / 10')
            ->assertSee('Rękawice')
            ->assertSee('12 / 12')
            ->assertSee('M · 7')
            ->assertSee('L · 3')
            ->assertSee('Rozmiar · dostępne')
            ->assertDontSee('nazwa wariantu');
    }

    public function test_can_issue_to_multiple_employees_without_variants(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $jan = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $gloves = Equipment::factory()->notReturnable()->withoutKinds()->create(['name' => 'Rękawice']);
        $variant = EquipmentVariant::factory()->unnamed()->inStock(10, 0, $this->warehouse)->create([
            'equipment_id' => $gloves->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->call('onEmployeesUpdated', [$anna->id, $jan->id])
            ->call('addToCart', $variant->id, 'given')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipment.tab.orders', ['warehouse_id' => $this->warehouse->id]));

        $this->assertSame(1, EquipmentIssue::query()->where('employee_id', $anna->id)->count());
        $this->assertSame(1, EquipmentIssue::query()->where('employee_id', $jan->id)->count());
        $this->assertSame(10, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(8, $variant->fresh()->availableIn($this->warehouse));

        $this->fulfillDispatch($this->latestDispatch())
            ->assertRedirect();

        $this->assertSame(8, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(8, $variant->fresh()->availableIn($this->warehouse));
        $this->assertCount(1, EquipmentIssue::query()->pluck('batch_id')->unique());
        $this->assertCount(1, EquipmentIssue::query()->pluck('warehouse_dispatch_id')->unique());
        $this->assertNotNull(EquipmentIssue::query()->value('warehouse_dispatch_id'));
    }

    public function test_variant_matrix_is_required_before_issuing_to_a_group(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $jan = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP', 'variant_label' => 'Rozmiar']);
        $sizeM = EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);
        $sizeL = EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $pants->id,
            'value' => 'L',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->call('onEmployeesUpdated', [$anna->id, $jan->id])
            ->call('addTypeToCart', $pants->id, 'returnable')
            ->assertSet('sizePanelTypeId', $pants->id)
            ->assertSee('rozmiar per osoba')
            ->assertSee('rozmiar 0/2')
            ->call('prepare')
            ->assertHasErrors('lines')
            ->assertSet('confirming', false);

        $this->assertSame(0, EquipmentIssue::query()->count());
        $this->assertSame(0, WarehouseDispatch::query()->count());

        $component
            ->call('setAssignmentVariant', $pants->id, $anna->id, $sizeM->id)
            ->call('setAssignmentVariant', $pants->id, $jan->id, $sizeL->id)
            ->call('prepare')
            ->assertHasNoErrors()
            ->assertSet('confirming', true)
            ->assertSee('Co kto dostaje')
            ->assertSee('Anna Nowak')
            ->assertSee('Jan Kowalski');

        $this->assertSame(0, EquipmentIssue::query()->count());

        $component
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipment.tab.orders', ['warehouse_id' => $this->warehouse->id]));

        $this->assertSame($sizeM->id, EquipmentIssue::query()->where('employee_id', $anna->id)->value('equipment_variant_id'));
        $this->assertSame($sizeL->id, EquipmentIssue::query()->where('employee_id', $jan->id)->value('equipment_variant_id'));
        $this->assertTrue(EquipmentIssue::query()->get()->every(fn (EquipmentIssue $issue) => $issue->status === EquipmentIssue::STATUS_RESERVED));
        $this->assertSame(5, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(5, $sizeL->fresh()->quantityIn($this->warehouse));
        $this->assertSame(4, $sizeM->fresh()->availableIn($this->warehouse));
        $this->assertSame(4, $sizeL->fresh()->availableIn($this->warehouse));

        $dispatch = $this->latestDispatch();
        $this->assertSame(sprintf('ZW-%d-0001', now()->year), $dispatch->number);
        $this->assertTrue($dispatch->isReserved());
        $this->assertCount(2, $dispatch->issues);
        $this->assertTrue($dispatch->issues->every(fn (EquipmentIssue $issue) => $issue->warehouse_dispatch_id === $dispatch->id));

        $this->actingAs($this->user)
            ->get(route('warehouse-dispatches.show', $dispatch))
            ->assertOk()
            ->assertSee($dispatch->number)
            ->assertSee('Kompletacja')
            ->assertSee('Wydaj odhaczone')
            ->assertSee('Anna Nowak')
            ->assertSee('Jan Kowalski')
            ->assertSee('Spodnie BHP')
            ->assertSee('form-check-table', false)
            ->assertSee('pick-issue-', false);

        $this->fulfillDispatch($dispatch)
            ->assertRedirect(route('warehouse-dispatches.show', $dispatch));

        $dispatch->refresh();
        $this->assertTrue($dispatch->isIssued());
        $this->assertSame(4, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(4, $sizeL->fresh()->quantityIn($this->warehouse));
        $this->assertSame(1, $sizeM->fresh()->issuedOutstandingIn($this->warehouse));
        $this->assertSame(1, $sizeL->fresh()->issuedOutstandingIn($this->warehouse));
    }

    public function test_size_panel_blocks_done_when_quantity_exceeds_stock(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Adamczyk']);
        $maria = Employee::factory()->create(['first_name' => 'Maria', 'last_name' => 'Górski']);
        $shoes = Equipment::factory()->create(['name' => 'Buty', 'variant_label' => 'Rozmiar']);
        $size42 = EquipmentVariant::factory()->inStock(1)->create([
            'equipment_id' => $shoes->id,
            'value' => '42',
        ]);
        EquipmentVariant::factory()->inStock(4)->create([
            'equipment_id' => $shoes->id,
            'value' => '43',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->call('onEmployeesUpdated', [$anna->id, $maria->id])
            ->call('addTypeToCart', $shoes->id, 'returnable')
            ->call('setAssignmentVariant', $shoes->id, $anna->id, $size42->id)
            ->call('setAssignmentQuantity', $shoes->id, $anna->id, 4)
            ->call('setAssignmentVariant', $shoes->id, $maria->id, $size42->id)
            ->call('setAssignmentQuantity', $shoes->id, $maria->id, 4)
            ->assertSet('sizePanelTypeId', $shoes->id)
            ->assertSee('wybrane 8')
            ->assertSee('dostępne 1')
            ->call('confirmSizePanel')
            ->assertSet('sizePanelTypeId', $shoes->id)
            ->assertHasErrors('sizePanel')
            ->assertSee('Rozmiar 42');

        $assignments = collect($component->get('lines')[0]['assignments'] ?? []);
        $this->assertSame(4, (int) $assignments->firstWhere('employee_id', $anna->id)['quantity']);
        $this->assertSame(4, (int) $assignments->firstWhere('employee_id', $maria->id)['quantity']);

        $component
            ->call('prepare')
            ->assertHasErrors('lines')
            ->assertSet('confirming', false);

        $this->assertSame(0, EquipmentIssue::query()->count());
    }

    public function test_show_page_lists_variant_and_available_stock(): void
    {
        $equipment = Equipment::factory()->create([
            'name' => 'Spodnie BHP',
            'variant_label' => 'Rozmiar',
        ]);
        EquipmentVariant::factory()->inStock(10, 2)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Spodnie BHP')
            ->assertSee('Rozmiar')
            ->assertSee('Rozkład sztuk')
            ->assertSee('Powyżej minimum')
            ->assertSee('min. 2')
            ->assertSee('Historia wydań')
            ->assertSee('Historia ruchów magazynowych')
            ->assertSee('Szukaj')
            ->assertSee('Przyjmij')
            ->assertSee('Wydaj')
            ->assertSee('Ruch magazynowy')
            ->assertSee('ostatnie 30 dni')
            ->assertSee($this->warehouse->display_name)
            ->assertDontSee('— siedziba')
            ->assertDontSee('Ostatnie wydania z tego magazynu')
            ->assertSee('Brak zdjęcia')
            ->assertDontSee('Dostępność')
            ->assertDontSee('Per wariant')
            ->assertDontSee('id="stock-movement-warehouse"', false);
    }

    public function test_issue_history_does_not_query_equipment_per_issue(): void
    {
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variants = collect([
            EquipmentVariant::factory()->inStock(10, 2)->create([
                'equipment_id' => $equipment->id,
                'value' => 'M',
            ]),
            EquipmentVariant::factory()->inStock(8, 2)->create([
                'equipment_id' => $equipment->id,
                'value' => 'L',
            ]),
            EquipmentVariant::factory()->inStock(6, 2)->create([
                'equipment_id' => $equipment->id,
                'value' => 'XL',
            ]),
        ]);

        foreach (range(1, 12) as $i) {
            EquipmentIssue::factory()->create([
                'equipment_id' => $equipment->id,
                'equipment_variant_id' => $variants[$i % 3]->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => EquipmentIssue::STATUS_ISSUED,
            ]);
        }

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();

        Livewire::actingAs($this->user)
            ->test(EquipmentIssueHistory::class, ['equipment' => $equipment])
            ->assertOk()
            ->assertSee('Spodnie BHP');

        $equipmentTableQueries = collect(\Illuminate\Support\Facades\DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $sql) => str_contains(strtolower($sql), 'from `equipment`')
                || str_contains(strtolower($sql), 'from "equipment"'))
            ->count();

        $this->assertLessThan(
            4,
            $equipmentTableQueries,
            'variant.sku should use eager-loaded equipment, not one query per issue'
        );
    }

    public function test_show_page_marks_variant_below_minimum_on_location_bars(): void
    {
        $equipment = Equipment::factory()->create([
            'name' => 'Spodnie BHP',
            'variant_label' => 'Rozmiar',
        ]);
        EquipmentVariant::factory()->inStock(4, 10)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);
        EquipmentVariant::factory()->inStock(20, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'L',
        ]);

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Poniżej minimum')
            ->assertSee('Brak minimum')
            ->assertSee('min. 10')
            ->assertSee($this->warehouse->display_name);
    }

    public function test_receipt_increases_stock_and_leaves_audit_trail(): void
    {
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(0, 2)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->call('startReceipt')
            ->assertSet('reason', StockMovementReason::Purchase->value)
            ->assertDontSee('Dodaj wariant')
            ->assertSee('Na półce')
            ->set('lines', [
                ['variant_id' => $variant->id, 'quantity' => 12],
            ])
            ->set('notes', 'FV 12/2026')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(12, $variant->fresh()->quantityIn($this->warehouse));
        $movement = EquipmentStockMovement::query()->first();
        $this->assertNotNull($movement);
        $this->assertSame(StockMovementType::RECEIPT, $movement->type);
        $this->assertSame(StockMovementReason::Purchase, $movement->reason);
        $this->assertSame(12, $movement->quantity);
        $this->assertSame('FV 12/2026', $movement->notes);
        $this->assertSame($this->user->id, $movement->created_by);
        $this->assertNotNull($movement->batch_id);

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Zakup')
            ->assertSee('+12 szt.')
            ->assertSee('FV 12/2026');
    }

    public function test_adjustment_decreases_stock_and_cannot_go_below_on_hand(): void
    {
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(5, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'L',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->call('startAdjustment')
            ->set('variantId', $variant->id)
            ->set('quantity', 9)
            ->call('save')
            ->assertHasErrors('quantity');

        $this->assertSame(5, $variant->fresh()->quantityIn($this->warehouse));

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->call('startAdjustment')
            ->set('variantId', $variant->id)
            ->set('quantity', 2)
            ->set('reason', StockMovementReason::Destruction->value)
            ->set('notes', 'inwentaryzacja')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(3, $variant->fresh()->quantityIn($this->warehouse));
        $movement = EquipmentStockMovement::query()->first();
        $this->assertSame(StockMovementType::ADJUSTMENT, $movement->type);
        $this->assertSame(StockMovementReason::Destruction, $movement->reason);

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Zniszczenie');
    }

    public function test_receipt_can_target_a_selected_warehouse(): void
    {
        $other = Warehouse::factory()->create(['name' => 'Magazyn Gdańsk']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(0, 0, $this->warehouse)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->assertDontSeeHtml('id="stock-movement-warehouse"')
            ->call('startReceipt')
            ->assertSeeHtml('id="stock-movement-warehouse"')
            ->assertSee('Magazyn')
            ->set('warehouseId', $other->id)
            ->set('lines', [
                ['variant_id' => $variant->id, 'quantity' => 7],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(0, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(7, $variant->fresh()->quantityIn($other));
        $this->assertSame($other->id, EquipmentStockMovement::query()->first()->warehouse_id);
    }

    public function test_receipt_accepts_multiple_variants_in_one_transaction(): void
    {
        $equipment = Equipment::factory()->create([
            'name' => 'Spodnie BHP',
            'variant_label' => 'Rozmiar',
        ]);
        $sizeM = EquipmentVariant::factory()->inStock(0, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);
        $sizeL = EquipmentVariant::factory()->inStock(1, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'L',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->call('startReceipt')
            ->assertDontSee('Dodaj wariant')
            ->assertSee('Na półce')
            ->assertSee('M')
            ->assertSee('L')
            ->set('lines', [
                ['variant_id' => $sizeM->id, 'quantity' => 10],
                ['variant_id' => $sizeL->id, 'quantity' => 4],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(10, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(5, $sizeL->fresh()->quantityIn($this->warehouse));

        $movements = EquipmentStockMovement::query()->orderBy('id')->get();
        $this->assertCount(2, $movements);
        $this->assertNotNull($movements[0]->batch_id);
        $this->assertSame($movements[0]->batch_id, $movements[1]->batch_id);
        $this->assertTrue($movements->every(fn (EquipmentStockMovement $movement) => $movement->type === StockMovementType::RECEIPT));

        $receipts = app(EquipmentService::class)
            ->stockTimeline($equipment)
            ->filter(fn (array $entry) => $entry['title'] === 'Zakup');
        $this->assertCount(1, $receipts);
        $this->assertSame('+14 szt.', $receipts->first()['quantity_label']);
        $this->assertCount(2, $receipts->first()['lines']);
    }

    public function test_product_page_movement_chart_covers_last_30_days_of_receipts_and_issues(): void
    {
        $employee = Employee::factory()->create();
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(20, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        EquipmentStockMovement::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'equipment_id' => $equipment->id,
            'equipment_variant_id' => $variant->id,
            'type' => StockMovementType::RECEIPT,
            'quantity' => 5,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
        EquipmentStockMovement::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'equipment_id' => $equipment->id,
            'equipment_variant_id' => $variant->id,
            'type' => StockMovementType::RECEIPT,
            'quantity' => 9,
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        $this->actingAs($this->user);
        app(EquipmentService::class)->issueAndFulfill($employee, $variant, $this->warehouse, 2);
        app(EquipmentService::class)->recordStockMovement(
            $variant,
            $this->warehouse,
            StockMovementType::ADJUSTMENT,
            1,
            StockMovementReason::Destruction
        );

        $oil = Equipment::factory()->notIssuable()->withoutKinds()->create(['name' => 'Olej silnikowy']);
        $oilVariant = EquipmentVariant::factory()->unnamed()->inStock(10, 0, $this->warehouse)->create([
            'equipment_id' => $oil->id,
        ]);
        $vehicle = Vehicle::factory()->create(['registration_number' => 'WZ 1234']);
        app(EquipmentService::class)->consumeItems(
            [['variant_id' => $oilVariant->id, 'quantity' => 3]],
            $this->warehouse,
            $vehicle
        );

        $chart = app(EquipmentService::class)->stockMovementChart($equipment);

        $this->assertSame(30, $chart['days']);
        $this->assertCount(30, $chart['labels']);
        $this->assertCount(30, $chart['stock']);
        $this->assertSame(5, $chart['inbound_total']);
        $this->assertSame(3, $chart['outbound_total']);
        $this->assertSame(17, $chart['stock_total']);
        $this->assertSame(17, $chart['stock'][29]);
        $this->assertContains(5, $chart['inbound']);
        $this->assertContains(3, $chart['outbound']);

        $oilChart = app(EquipmentService::class)->stockMovementChart($oil);
        $this->assertSame(3, $oilChart['outbound_total']);
        $this->assertSame(7, $oilChart['stock_total']);
        $this->assertSame(7, $oilChart['stock'][29]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockTimeline::class, ['equipment' => $equipment])
            ->assertSee('Ruch magazynowy')
            ->assertSee('ostatnie 30 dni')
            ->assertSee('Przyjęcia')
            ->assertSee('Rozchody')
            ->assertSee('Stan')
            ->assertSee('Zniszczenie');
    }

    public function test_can_issue_from_product_page_and_history_shows_issue_and_return(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(10, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->call('startIssue')
            ->set('warehouseId', $this->warehouse->id)
            ->set('employeeId', $employee->id)
            ->set('lines', [
                ['variant_id' => $variant->id, 'quantity' => 2],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $issue = EquipmentIssue::query()->first();
        $this->assertNotNull($issue);
        $this->assertSame(EquipmentIssue::STATUS_ISSUED, $issue->status);
        $this->assertSame(8, $variant->fresh()->quantityIn($this->warehouse));

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Wydanie na zlecenie')
            ->assertSee($issue->dispatch->number)
            ->assertSee('-2 szt.')
            ->assertSee('Anna Nowak');

        $this->actingAs($this->user)
            ->post(route('equipment-issues.return.store', $issue), [
                'return_date' => now()->toDateString(),
                'status' => 'returned',
            ])
            ->assertRedirect();

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Zwrot')
            ->assertSee('+2 szt.');
    }

    public function test_product_timeline_groups_issue_lines_by_dispatch(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $jan = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(10, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->call('onEmployeesUpdated', [$anna->id, $jan->id])
            ->call('addToCart', $variant->id, 'returnable')
            ->call('save')
            ->assertHasNoErrors();

        $dispatch = $this->latestDispatch();
        $this->fulfillDispatch($dispatch)->assertRedirect();

        $entries = app(EquipmentService::class)->stockTimeline($equipment);
        $issueEntries = $entries->filter(fn (array $entry) => str_starts_with($entry['title'], 'Wydanie na zlecenie'));

        $this->assertCount(1, $issueEntries);
        $grouped = $issueEntries->first();
        $this->assertSame('-2 szt.', $grouped['quantity_label']);
        $this->assertSame($dispatch->number, str_replace('Wydanie na zlecenie ', '', $grouped['title']));
        $this->assertCount(2, $grouped['lines']);

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee($dispatch->number)
            ->assertSee('-2 szt.')
            ->assertSee('Anna Nowak')
            ->assertSee('Jan Kowalski');
    }

    public function test_issue_history_on_product_page_can_be_searched_and_sorted(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $jan = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(10, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        $this->actingAs($this->user);
        $service = app(EquipmentService::class);
        $service->issueAndFulfill($anna, $variant, $this->warehouse, 1);
        $service->issueAndFulfill($jan, $variant, $this->warehouse, 2);

        Livewire::actingAs($this->user)
            ->test(EquipmentIssueHistory::class, ['equipment' => $equipment])
            ->assertSee('Anna Nowak')
            ->assertSee('Jan Kowalski')
            ->set('search', 'Anna')
            ->assertSee('Anna Nowak')
            ->assertDontSee('Jan Kowalski')
            ->call('clearFilters')
            ->assertSee('Jan Kowalski')
            ->call('sortBy', 'quantity')
            ->assertSet('sortField', 'quantity')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_issue_history_lists_consumptions_and_offers_return_next_to_view(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $pantsVariant = EquipmentVariant::factory()->inStock(10, 0)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);
        $oil = Equipment::factory()->notIssuable()->withoutKinds()->create(['name' => 'Olej silnikowy']);
        $oilVariant = EquipmentVariant::factory()->unnamed()->inStock(10, 0, $this->warehouse)->create([
            'equipment_id' => $oil->id,
        ]);
        $vehicle = Vehicle::factory()->create([
            'registration_number' => 'WZ 9999',
            'brand' => 'Ford',
            'model' => 'Transit',
        ]);

        $this->actingAs($this->user);
        $service = app(EquipmentService::class);
        $issue = $service->issueAndFulfill($anna, $pantsVariant, $this->warehouse, 1);
        $service->consumeItems([['variant_id' => $oilVariant->id, 'quantity' => 2]], $this->warehouse, $vehicle, 'wymiana oleju');

        Livewire::actingAs($this->user)
            ->test(EquipmentIssueHistory::class, ['equipment' => $pants])
            ->assertSee('Anna Nowak')
            ->assertSeeHtml(route('equipment-issues.return', $issue));

        Livewire::actingAs($this->user)
            ->test(EquipmentIssueHistory::class, ['equipment' => $oil])
            ->assertSee('Rozchód')
            ->assertSee('wymiana oleju')
            ->assertDontSee('Ta pozycja nie jest wydawana pracownikom.');
    }

    public function test_product_timeline_includes_damaged_and_lost_returns(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(10, 0)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        $this->actingAs($this->user);
        $issue = app(EquipmentService::class)->issueAndFulfill($employee, $variant, $this->warehouse, 1);
        $this->post(route('equipment-issues.return.store', $issue), [
            'return_date' => now()->toDateString(),
            'status' => 'damaged',
        ])->assertRedirect();

        $entries = app(EquipmentService::class)->stockTimeline($equipment);
        $this->assertTrue($entries->contains(fn (array $entry) => $entry['title'] === 'Uszkodzenie'));

        $this->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Uszkodzenie');
    }

    public function test_editing_catalog_does_not_overwrite_stock_quantity(): void
    {
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(10, 2)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->set('name', 'Spodnie BHP zimowe')
            ->set('has_variants', true)
            ->set('variant_label', 'Rozmiar')
            ->set('variants', [
                ['id' => $variant->id, 'value' => 'M', 'min_quantity' => 5],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(10, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(5, $variant->fresh()->minQuantityIn($this->warehouse));
        $this->assertSame(0, EquipmentStockMovement::query()->count());
    }

    public function test_can_attach_and_remove_equipment_image(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->user)
            ->test(EquipmentForm::class, ['warehouse' => $this->warehouse])
            ->set('name', 'Spodnie ze zdjęciem')
            ->set('has_variants', false)
            ->set('issuable', true)
            ->set('returnable', true)
            ->set('variants', [
                ['id' => null, 'value' => '', 'quantity_in_stock' => 3, 'min_quantity' => 0],
            ])
            ->set('image', UploadedFile::fake()->image('spodnie.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $equipment = Equipment::query()->where('name', 'Spodnie ze zdjęciem')->first();
        $this->assertNotNull($equipment);
        $this->assertNotNull($equipment->image_path);
        Storage::disk('public')->assertExists($equipment->image_path);

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee($equipment->image_url, false)
            ->assertDontSee('Brak zdjęcia');

        $oldPath = $equipment->image_path;

        Livewire::actingAs($this->user)
            ->test(EquipmentForm::class, ['equipment' => $equipment->fresh(['variants.stocks']), 'warehouse' => $this->warehouse])
            ->set('removeImage', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($equipment->fresh()->image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_reserve_fulfill_return_increments_stock_and_damage_does_not_double_decrement(): void
    {
        $employee = Employee::factory()->create();
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP', 'variant_label' => 'Rozmiar']);
        $sizeM = EquipmentVariant::factory()->inStock(10)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->set('employeeIds', [$employee->id])
            ->call('addToCart', $sizeM->id, 'returnable', 2)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(10, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(8, $sizeM->fresh()->availableIn($this->warehouse));

        $issue = EquipmentIssue::query()->first();
        $this->assertSame(EquipmentIssue::STATUS_RESERVED, $issue->status);

        $this->actingAs($this->user)
            ->get(route('equipment-issues.return', $issue))
            ->assertRedirect(route('equipment-issues.show', $issue));

        $this->fulfillDispatch($this->latestDispatch())
            ->assertRedirect();

        $this->assertSame(EquipmentIssue::STATUS_ISSUED, $issue->fresh()->status);
        $this->assertSame(8, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(2, $sizeM->fresh()->issuedOutstandingIn($this->warehouse));

        $this->actingAs($this->user)
            ->post(route('equipment-issues.return.store', $issue), [
                'return_date' => now()->toDateString(),
                'status' => 'returned',
            ])
            ->assertRedirect(route('equipment-issues.show', $issue));

        $this->assertSame(EquipmentIssue::STATUS_RETURNED, $issue->fresh()->status);
        $this->assertSame(10, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(0, $sizeM->fresh()->issuedOutstandingIn($this->warehouse));

        $jan = Employee::factory()->create();
        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->set('employeeIds', [$jan->id])
            ->call('addToCart', $sizeM->id, 'returnable', 1)
            ->call('save')
            ->assertHasNoErrors();

        $damaged = EquipmentIssue::query()->where('employee_id', $jan->id)->first();
        $this->fulfillDispatch($this->latestDispatch())
            ->assertRedirect();

        $this->assertSame(9, $sizeM->fresh()->quantityIn($this->warehouse));

        $this->actingAs($this->user)
            ->post(route('equipment-issues.return.store', $damaged), [
                'return_date' => now()->toDateString(),
                'status' => 'damaged',
            ])
            ->assertRedirect(route('equipment-issues.show', $damaged));

        $this->assertSame(EquipmentIssue::STATUS_DAMAGED, $damaged->fresh()->status);
        $this->assertSame(9, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(0, $sizeM->fresh()->issuedOutstandingIn($this->warehouse));
    }

    public function test_partial_picking_issues_only_checked_lines_and_releases_the_rest(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $jan = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP', 'variant_label' => 'Rozmiar']);
        $sizeM = EquipmentVariant::factory()->inStock(10)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->call('onEmployeesUpdated', [$anna->id, $jan->id])
            ->call('addToCart', $sizeM->id, 'returnable')
            ->call('save')
            ->assertHasNoErrors();

        $dispatch = $this->latestDispatch();
        $annasIssue = EquipmentIssue::query()->where('employee_id', $anna->id)->first();
        $jansIssue = EquipmentIssue::query()->where('employee_id', $jan->id)->first();

        $this->fulfillDispatch($dispatch, [$annasIssue->id])
            ->assertRedirect(route('warehouse-dispatches.show', $dispatch));

        $dispatch->refresh();
        $this->assertTrue($dispatch->isPartial());
        $this->assertSame(EquipmentIssue::STATUS_ISSUED, $annasIssue->fresh()->status);
        $this->assertSame(EquipmentIssue::STATUS_UNFULFILLED, $jansIssue->fresh()->status);
        $this->assertSame(9, $sizeM->fresh()->quantityIn($this->warehouse));
        $this->assertSame(9, $sizeM->fresh()->availableIn($this->warehouse));
        $this->assertSame(1, $sizeM->fresh()->issuedOutstandingIn($this->warehouse));

        $this->actingAs($this->user)
            ->get(route('warehouse-dispatches.show', $dispatch))
            ->assertOk()
            ->assertSee('Częściowo wydane')
            ->assertSee('Nie wydano');
    }

    public function test_employee_show_lists_issues_and_returns_for_that_person(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $jan = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $pants = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $helmet = Equipment::factory()->create(['name' => 'Kask BHP']);
        $pantsM = EquipmentVariant::factory()->inStock(10, 0)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);
        $helmetM = EquipmentVariant::factory()->inStock(10, 0)->create([
            'equipment_id' => $helmet->id,
            'value' => 'M',
        ]);

        $this->actingAs($this->user);
        $service = app(EquipmentService::class);
        $annasIssue = $service->issueAndFulfill($anna, $pantsM, $this->warehouse, 2);
        $service->issueAndFulfill($jan, $helmetM, $this->warehouse, 1);

        $this->get(route('employees.show', ['employee' => $anna, 'tab' => 'equipment']))
            ->assertOk()
            ->assertSee('Asortyment')
            ->assertSee('Aktualnie u pracownika')
            ->assertSee('Spodnie BHP')
            ->assertSee($annasIssue->issue_date->format('Y-m-d'))
            ->assertSee('Do zwrotu')
            ->assertDontSee('Kask BHP');

        $this->post(route('equipment-issues.return.store', $annasIssue), [
            'return_date' => now()->toDateString(),
            'status' => 'returned',
        ])->assertRedirect();

        $this->get(route('employees.show', ['employee' => $anna, 'tab' => 'equipment']))
            ->assertOk()
            ->assertSee('Spodnie BHP')
            ->assertSee('Zwrócony')
            ->assertSee(now()->toDateString())
            ->assertDontSee('Aktualnie u pracownika');

        Livewire::actingAs($this->user)
            ->test(EmployeeEquipmentHistory::class, ['employee' => $anna])
            ->assertSee('Spodnie BHP')
            ->assertDontSee('Kask BHP')
            ->set('search', 'Kask')
            ->assertDontSee('Spodnie BHP')
            ->call('clearFilters')
            ->assertSee('Spodnie BHP')
            ->set('statusFilter', 'returned')
            ->assertSee('Zwrócony');
    }

    public function test_transfer_moves_stock_between_warehouses_and_groups_timeline(): void
    {
        $other = Warehouse::factory()->create(['name' => 'Magazyn Gdańsk']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(10, 0, $this->warehouse)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->assertSee('Przemieść')
            ->call('startTransfer')
            ->assertSeeHtml('id="stock-movement-target-warehouse"')
            ->assertSee('Z magazynu')
            ->assertSee('Do magazynu')
            ->assertDontSeeHtml('id="stock-movement-reason"')
            ->set('warehouseId', $this->warehouse->id)
            ->set('targetWarehouseId', $other->id)
            ->set('lines', [
                ['variant_id' => $variant->id, 'quantity' => 4],
            ])
            ->set('notes', 'MM na budowę')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(6, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(4, $variant->fresh()->quantityIn($other));

        $movements = EquipmentStockMovement::query()->orderBy('id')->get();
        $this->assertCount(2, $movements);
        $this->assertSame(StockMovementType::TRANSFER_OUT, $movements[0]->type);
        $this->assertSame(StockMovementType::TRANSFER_IN, $movements[1]->type);
        $this->assertSame($movements[0]->batch_id, $movements[1]->batch_id);
        $this->assertSame($other->id, $movements[0]->related_warehouse_id);
        $this->assertSame($this->warehouse->id, $movements[1]->related_warehouse_id);
        $this->assertNull($movements[0]->logistics_event_id);

        $entries = app(EquipmentService::class)->stockTimeline($equipment);
        $transfers = $entries->filter(fn (array $entry) => $entry['title'] === 'Przemieszczenie');
        $this->assertCount(1, $transfers);
        $this->assertSame('4 szt.', $transfers->first()['quantity_label']);
        $this->assertSame(0, $transfers->first()['signed_quantity']);

        $chart = app(EquipmentService::class)->stockMovementChart($equipment);
        $this->assertSame(0, $chart['inbound_total']);
        $this->assertSame(0, $chart['outbound_total']);
        $this->assertSame(10, $chart['stock_total']);
        $this->assertSame(10, $chart['stock'][29]);

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Przemieszczenie')
            ->assertSee('4 szt.')
            ->assertSee('MM na budowę');
    }

    public function test_transfer_cannot_exceed_available_or_use_the_same_warehouse(): void
    {
        $other = Warehouse::factory()->create(['name' => 'Magazyn Gdańsk']);
        $employee = Employee::factory()->create();
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(5, 0, $this->warehouse)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);

        $this->actingAs($this->user);
        $service = app(EquipmentService::class);

        try {
            $service->transferStock($variant, $this->warehouse, $this->warehouse, 1);
            $this->fail('Expected ValidationException for the same warehouse.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('targetWarehouseId', $e->errors());
        }

        $service->issueItems(
            $employee,
            [['variant_id' => $variant->id, 'quantity' => 2]],
            $this->warehouse,
            now()
        );

        try {
            $service->transferStock($variant, $this->warehouse, $other, 4);
            $this->fail('Expected ValidationException when transferring more than available.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }

        $this->assertSame(5, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(0, $variant->fresh()->quantityIn($other));

        $service->transferStock($variant, $this->warehouse, $other, 3);

        $this->assertSame(2, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(3, $variant->fresh()->quantityIn($other));
    }

    public function test_transfer_can_link_optional_logistics_event(): void
    {
        $other = Warehouse::factory()->create(['name' => 'Magazyn Gdańsk']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(8, 0, $this->warehouse)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);
        $event = $this->logisticsEvent($other, 'Warsztat Gdańsk');

        Livewire::actingAs($this->user)
            ->test(EquipmentStockMovementForm::class, [
                'equipment' => $equipment,
                'warehouse' => $this->warehouse,
            ])
            ->call('startTransfer')
            ->set('warehouseId', $this->warehouse->id)
            ->set('targetWarehouseId', $other->id)
            ->set('lines', [
                ['variant_id' => $variant->id, 'quantity' => 2],
            ])
            ->set('logisticsEventSearch', 'Warsztat Gdańsk')
            ->assertSee('Transfer #'.$event->id)
            ->call('selectLogisticsEvent', $event->id)
            ->assertSet('logisticsEventId', $event->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $movements = EquipmentStockMovement::query()->get();
        $this->assertCount(2, $movements);
        $this->assertTrue($movements->every(fn (EquipmentStockMovement $movement) => $movement->logistics_event_id === $event->id));

        $this->actingAs($this->user)
            ->get(route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Przemieszczenie')
            ->assertSee(route('transfers.show', $event), false);
    }

    public function test_departure_show_lists_warehouse_transfers_and_add_button(): void
    {
        $other = Warehouse::factory()->create(['name' => 'Magazyn Gdańsk']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(8, 0, $this->warehouse)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);
        $departure = $this->departureEvent();

        $this->actingAs($this->user);
        app(EquipmentService::class)->transferStock(
            $variant,
            $this->warehouse,
            $other,
            2,
            $departure,
            'MM na wyjazd'
        );

        $this->get(route('departures.show', $departure))
            ->assertOk()
            ->assertSee('Transfer międzymagazynowy')
            ->assertSee('Dodaj przemieszczenie')
            ->assertSee('Spodnie BHP')
            ->assertSee('Magazyn Gdańsk')
            ->assertSee('MM na wyjazd')
            ->assertSee('2 szt.');
    }

    public function test_can_add_warehouse_transfer_from_departure(): void
    {
        $other = Warehouse::factory()->create(['name' => 'Magazyn Gdańsk']);
        $equipment = Equipment::factory()->create(['name' => 'Spodnie BHP']);
        $variant = EquipmentVariant::factory()->inStock(8, 0, $this->warehouse)->create([
            'equipment_id' => $equipment->id,
            'value' => 'M',
        ]);
        $departure = $this->departureEvent();

        Livewire::actingAs($this->user)
            ->test(LogisticsEventWarehouseTransfers::class, ['event' => $departure])
            ->assertSee('Transfer międzymagazynowy')
            ->assertSee('Dodaj przemieszczenie')
            ->call('startAdding')
            ->assertSeeHtml('id="event-wh-from"')
            ->set('warehouseId', $this->warehouse->id)
            ->set('targetWarehouseId', $other->id)
            ->set('addEquipmentId', $equipment->id)
            ->set('addVariantId', $variant->id)
            ->set('addQuantity', 3)
            ->call('addLine')
            ->assertHasNoErrors()
            ->set('notes', 'Na wyjazd 164')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Spodnie BHP')
            ->assertSee('Na wyjazd 164')
            ->assertSee('Magazyn Gdańsk');

        $this->assertSame(5, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(3, $variant->fresh()->quantityIn($other));

        $movements = EquipmentStockMovement::query()->orderBy('id')->get();
        $this->assertCount(2, $movements);
        $this->assertTrue($movements->every(
            fn (EquipmentStockMovement $movement) => $movement->logistics_event_id === $departure->id
        ));
        $this->assertSame($movements[0]->batch_id, $movements[1]->batch_id);
    }

    public function test_cannot_add_warehouse_transfer_to_cancelled_departure(): void
    {
        Warehouse::factory()->create(['name' => 'Magazyn Gdańsk']);
        $departure = $this->departureEvent();
        $departure->update(['status' => LogisticsEventStatus::CANCELLED]);

        Livewire::actingAs($this->user)
            ->test(LogisticsEventWarehouseTransfers::class, ['event' => $departure])
            ->assertDontSee('Dodaj przemieszczenie')
            ->call('startAdding')
            ->assertSet('adding', false)
            ->call('save')
            ->assertHasErrors(['action']);
    }

    private function latestDispatch(): WarehouseDispatch
    {
        $dispatch = WarehouseDispatch::query()->latest('id')->first();
        $this->assertNotNull($dispatch);

        return $dispatch;
    }

    /**
     * @param  list<int>|null  $issueIds
     */
    private function fulfillDispatch(WarehouseDispatch $dispatch, ?array $issueIds = null): \Illuminate\Testing\TestResponse
    {
        $ids = $issueIds ?? $dispatch->issues()
            ->where('status', EquipmentIssue::STATUS_RESERVED)
            ->pluck('id')
            ->all();

        return $this->actingAs($this->user)
            ->post(route('warehouse-dispatches.fulfill', $dispatch), [
                'issue_ids' => $ids,
            ]);
    }

    private function logisticsEvent(Warehouse $destination, string $toLocationName): LogisticsEvent
    {
        $fromLocationId = $this->warehouse->location_id
            ?? Location::factory()->create(['name' => 'Siedziba'])->id;
        $toLocation = $destination->location_id
            ? Location::query()->find($destination->location_id)
            : Location::factory()->create(['name' => $toLocationName]);

        if ($toLocation->name !== $toLocationName) {
            $toLocation->update(['name' => $toLocationName]);
        }

        return LogisticsEvent::query()->create([
            'type' => LogisticsEventType::TRANSFER,
            'event_date' => now(),
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocation->id,
            'status' => LogisticsEventStatus::PLANNED,
            'created_by' => $this->user->id,
        ]);
    }

    private function departureEvent(): LogisticsEvent
    {
        $fromLocationId = $this->warehouse->location_id
            ?? Location::factory()->create(['name' => 'Baza'])->id;
        $toLocation = Location::factory()->create(['name' => 'Budowa']);

        return LogisticsEvent::query()->create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => now()->addDay(),
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocation->id,
            'status' => LogisticsEventStatus::PLANNED,
            'created_by' => $this->user->id,
        ]);
    }
}
