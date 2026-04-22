<?php

namespace Tests\Unit\Livewire\Concerns;

use App\Livewire\Concerns\ManagesVehicleSeats;
use PHPUnit\Framework\TestCase;

/**
 * Klasa pomocnicza — udostępnia chronione metody traita do testów.
 */
class ManagesVehicleSeatsTester
{
    use ManagesVehicleSeats;

    /** @var array<int, array<string, mixed>> */
    public array $vehicleSeats = [];

    public function exposeBuildSeatRow(int $seatIndex, ?int $employeeId = null, ?string $position = null, ?bool $externalDriver = null): array
    {
        return $this->buildSeatRow($seatIndex, $employeeId, $position, $externalDriver);
    }

    public function exposeNormalize(int $seatIndex, array $row): array
    {
        return $this->normalizeSeatRowFromPartial($seatIndex, $row);
    }

    public function exposeApply(array $data): void
    {
        $this->applyVehicleSeatUpdateFromChild($data);
    }

    public function exposeCompact(): void
    {
        $this->compactPassengerSeats();
    }
}

class ManagesVehicleSeatsTest extends TestCase
{
    public function test_build_seat_row_driver_with_employee(): void
    {
        $t = new ManagesVehicleSeatsTester;
        $r = $t->exposeBuildSeatRow(0, 7, 'driver', false);

        $this->assertSame(7, $r['employee_id']);
        $this->assertSame('driver', $r['position']);
        $this->assertFalse($r['external_driver']);
    }

    public function test_build_seat_row_driver_external_defaults_true(): void
    {
        $t = new ManagesVehicleSeatsTester;
        $r = $t->exposeBuildSeatRow(0, null, 'driver', null);

        $this->assertNull($r['employee_id']);
        $this->assertTrue($r['external_driver']);
    }

    public function test_compact_passenger_seats_removes_gaps(): void
    {
        $t = new ManagesVehicleSeatsTester;
        $t->vehicleSeats = [
            0 => ['employee_id' => null, 'position' => 'driver', 'external_driver' => true],
            1 => ['employee_id' => null, 'position' => 'passenger', 'external_driver' => false],
            2 => ['employee_id' => 10, 'position' => 'passenger', 'external_driver' => false],
            3 => ['employee_id' => null, 'position' => 'passenger', 'external_driver' => false],
            4 => ['employee_id' => 20, 'position' => 'passenger', 'external_driver' => false],
        ];

        $t->exposeCompact();

        $this->assertSame(10, $t->vehicleSeats[1]['employee_id']);
        $this->assertSame(20, $t->vehicleSeats[2]['employee_id']);
        $this->assertNull($t->vehicleSeats[3]['employee_id']);
        $this->assertNull($t->vehicleSeats[4]['employee_id']);
    }

    public function test_apply_child_update_preserves_external_driver_when_not_sent(): void
    {
        $t = new ManagesVehicleSeatsTester;
        $t->vehicleSeats = [
            0 => ['employee_id' => null, 'position' => 'driver', 'external_driver' => false],
        ];

        $t->exposeApply([
            'seat_index' => 0,
            'employee_id' => null,
            'position' => 'driver',
        ]);

        $this->assertFalse($t->vehicleSeats[0]['external_driver']);
    }

    public function test_apply_child_update_clears_external_when_assigning_driver(): void
    {
        $t = new ManagesVehicleSeatsTester;
        $t->vehicleSeats = [
            0 => ['employee_id' => null, 'position' => 'driver', 'external_driver' => true],
        ];

        $t->exposeApply([
            'seat_index' => 0,
            'employee_id' => 99,
            'position' => 'driver',
        ]);

        $this->assertSame(99, $t->vehicleSeats[0]['employee_id']);
        $this->assertFalse($t->vehicleSeats[0]['external_driver']);
    }

    public function test_normalize_partial_adds_external_driver_key(): void
    {
        $t = new ManagesVehicleSeatsTester;
        $r = $t->exposeNormalize(1, ['employee_id' => 3, 'position' => 'passenger']);

        $this->assertSame(3, $r['employee_id']);
        $this->assertFalse($r['external_driver']);
    }
}
