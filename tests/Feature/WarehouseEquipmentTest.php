<?php

namespace Tests\Feature;

use App\Enums\LocationPurposeType;
use App\Livewire\EquipmentForm;
use App\Livewire\WarehouseConsumeForm;
use App\Livewire\WarehouseIssueForm;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentStock;
use App\Models\EquipmentVariant;
use App\Models\Location;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertOk()
            ->assertSee('Magazyny')
            ->assertSee('Dodaj magazyn')
            ->assertSee($this->warehouse->name)
            ->assertSee('Wydaj')
            ->assertSee('Rozchód');

        $this->actingAs($this->user)
            ->get(route('equipment.index'))
            ->assertOk()
            ->assertSee('Magazyn')
            ->assertSee('Dodaj do magazynu')
            ->assertSee('Stan magazynu')
            ->assertSee('Wydania');
    }

    public function test_issue_and_consume_are_full_pages_from_warehouse_tabs(): void
    {
        $this->actingAs($this->user)
            ->get(route('equipment.tab.issues'))
            ->assertOk()
            ->assertSee('Stan magazynu')
            ->assertSee('Wydania')
            ->assertSee('Wydaj')
            ->assertSee('Wydaj bezzwrotnie')
            ->assertSee('Rozchód');

        $this->actingAs($this->user)
            ->get(route('equipment-issues.create', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Wydaj do zwrotu')
            ->assertSee('Wydanie do zwrotu');

        $this->actingAs($this->user)
            ->get(route('equipment-issues.create', ['warehouse_id' => $this->warehouse->id, 'mode' => 'given']))
            ->assertOk()
            ->assertSee('Wydaj bezzwrotnie')
            ->assertSee('Wydanie bezzwrotne');

        $this->actingAs($this->user)
            ->get(route('equipment-consumptions.create', ['warehouse_id' => $this->warehouse->id]))
            ->assertOk()
            ->assertSee('Rozchód');
    }

    public function test_can_create_type_with_kinds_via_livewire(): void
    {
        Livewire::actingAs($this->user)
            ->test(EquipmentForm::class, ['warehouse' => $this->warehouse])
            ->set('name', 'Spodnie BHP')
            ->set('category', 'Odzież BHP')
            ->set('has_variants', true)
            ->set('variant_label', 'Rozmiar')
            ->set('issuable', true)
            ->set('returnable', true)
            ->set('variants', [
                ['id' => null, 'value' => 'M', 'quantity_in_stock' => 10, 'min_quantity' => 2],
                ['id' => null, 'value' => 'L', 'quantity_in_stock' => 4, 'min_quantity' => 1],
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
        $this->assertSame(10, $equipment->variants->firstWhere('value', 'M')->quantityIn($this->warehouse));
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
                ['id' => null, 'value' => '', 'quantity_in_stock' => 4, 'min_quantity' => 1],
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
        $this->assertSame(4, $equipment->variants->first()->quantityIn($this->warehouse));

        $this->actingAs($this->user)
            ->get(route('equipment.index'))
            ->assertOk()
            ->assertSee('Opony zamienne')
            ->assertSee('Niewydawalny')
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
            ->assertSee('W innych magazynach')
            ->assertSee('Do zwrotu tutaj')
            ->assertSee('Do zwrotu w innych magazynach')
            ->assertSee('Okulary')
            ->assertSee('Przeciwsłoneczne UV')
            ->assertSee('UV400')
            ->assertSee('Wydawalny')
            ->assertSee('Zwracalny');
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
            ->assertSee('Młotek');

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
        EquipmentIssue::factory()->create([
            'equipment_id' => $equipment->id,
            'equipment_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
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
            ->assertSee('Magazyn Gdańsk');
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
            ->set('equipmentSearch', 'Spodnie')
            ->assertSee('Spodnie BHP')
            ->set('equipmentSearch', 'Opony')
            ->assertDontSee('Opony zamienne')
            ->set('equipmentSearch', 'Rękawice')
            ->assertDontSee('Rękawice');
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
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse, 'mode' => 'given'])
            ->set('equipmentSearch', 'Rękawice')
            ->assertSee('Rękawice')
            ->set('equipmentSearch', 'Spodnie')
            ->assertDontSee('Spodnie BHP')
            ->set('employeeId', $employee->id)
            ->set('addEquipmentId', $gloves->id)
            ->set('addVariantId', $variant->id)
            ->set('addQuantity', 3)
            ->call('addLine')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipment.tab.issues'));

        $issue = EquipmentIssue::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($issue);
        $this->assertSame(EquipmentIssue::STATUS_GIVEN, $issue->status);
        $this->assertSame(7, $variant->fresh()->quantityIn($this->warehouse));
        $this->assertSame(0, $variant->fresh()->issuedOutstandingIn($this->warehouse));

        $this->actingAs($this->user)
            ->get(route('equipment.tab.issues'))
            ->assertOk()
            ->assertSee('Wydanie bezzwrotne')
            ->assertSee('Bezzwrotne')
            ->assertDontSee('Zwróć/Zgłoś');
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
            ->set('employeeId', $employee->id)
            ->set('addEquipmentId', $pants->id)
            ->set('addVariantId', $sizeM->id)
            ->set('addQuantity', 2)
            ->call('addLine')
            ->set('addEquipmentId', $glasses->id)
            ->set('addVariantId', $uv->id)
            ->set('addQuantity', 1)
            ->call('addLine')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipment.tab.issues'));

        $this->assertSame(2, EquipmentIssue::query()->where('employee_id', $employee->id)->count());
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
            ->set('addEquipmentId', $pants->id)
            ->set('addVariantId', $sizeM->id)
            ->set('addQuantity', 3)
            ->call('addLine');

        $this->assertSame(7, $component->instance()->remainingFor($sizeM->id));
        $component->assertSee('Zostanie')->assertSee('-3');
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
            ->set('addEquipmentId', $pants->id)
            ->set('addVariantId', $sizeM->id)
            ->set('addQuantity', 5)
            ->call('addLine')
            ->assertHasErrors('addQuantity');
    }

    public function test_save_and_next_keeps_form_for_another_person(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $pants = Equipment::factory()->create();
        $sizeM = EquipmentVariant::factory()->inStock(5)->create([
            'equipment_id' => $pants->id,
            'value' => 'M',
        ]);

        Livewire::actingAs($this->user)
            ->test(WarehouseIssueForm::class, ['warehouse' => $this->warehouse])
            ->set('employeeId', $employee->id)
            ->set('addEquipmentId', $pants->id)
            ->set('addVariantId', $sizeM->id)
            ->set('addQuantity', 1)
            ->call('addLine')
            ->call('saveAndNext')
            ->assertHasNoErrors()
            ->assertSet('employeeId', null)
            ->assertSet('lines', [])
            ->assertSee('Możesz wydać kolejnej osobie');

        $this->assertSame(1, EquipmentIssue::query()->where('employee_id', $employee->id)->count());
        $this->assertSame(4, $sizeM->fresh()->availableIn($this->warehouse));
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
            ->set('employeeId', $employee->id)
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
        $this->assertSame(1, \App\Models\EquipmentStockMovement::query()->count());
        $this->assertSame($employee->id, \App\Models\EquipmentStockMovement::query()->first()->employee_id);
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
}
