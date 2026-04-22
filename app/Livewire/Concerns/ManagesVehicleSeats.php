<?php

namespace App\Livewire\Concerns;

/**
 * Wspólna struktura miejsc w aucie oraz operacje na tablicy {@see $vehicleSeats}.
 * Seat 0 = kierowca (external_driver gdy brak przypisanego pracownika).
 *
 * @property array<int, array<string, mixed>> $vehicleSeats
 */
trait ManagesVehicleSeats
{
    /**
     * Uzupełnia brakujące klucze w istniejącym wierszu (np. dane z props / JSON).
     *
     * @param  array<string, mixed>  $row
     * @return array{employee_id: ?int, position: string, external_driver: bool}
     */
    protected function normalizeSeatRowFromPartial(int $seatIndex, array $row): array
    {
        $eid = self::nullableIntEmployeeId($row['employee_id'] ?? null);

        if ($seatIndex === 0) {
            if ($eid !== null) {
                return $this->buildSeatRow(0, $eid, 'driver', false);
            }

            return $this->buildSeatRow(
                0,
                null,
                'driver',
                array_key_exists('external_driver', $row) ? (bool) $row['external_driver'] : true
            );
        }

        $pos = isset($row['position']) ? (string) $row['position'] : 'passenger';
        if ($pos === 'driver') {
            $pos = 'passenger';
        }

        return $this->buildSeatRow($seatIndex, $eid, $pos, false);
    }

    /**
     * @return array{employee_id: ?int, position: string, external_driver: bool}
     */
    protected function buildSeatRow(int $seatIndex, ?int $employeeId = null, ?string $position = null, ?bool $externalDriver = null): array
    {
        if ($seatIndex === 0) {
            if ($employeeId !== null) {
                return [
                    'employee_id' => $employeeId,
                    'position' => 'driver',
                    'external_driver' => false,
                ];
            }

            return [
                'employee_id' => null,
                'position' => 'driver',
                'external_driver' => $externalDriver !== null ? (bool) $externalDriver : true,
            ];
        }

        return [
            'employee_id' => $employeeId,
            'position' => $position ?? 'passenger',
            'external_driver' => false,
        ];
    }

    /**
     * Skompaktuj miejsca pasażerów (1..n), żeby zajęte były z przodu bez dziur.
     */
    protected function compactPassengerSeats(): void
    {
        $capacity = count($this->vehicleSeats);
        if ($capacity <= 1) {
            return;
        }

        $occupied = [];
        for ($i = 1; $i < $capacity; $i++) {
            $eid = $this->vehicleSeats[$i]['employee_id'] ?? null;
            if ($eid !== null && $eid !== '') {
                $occupied[] = (int) $eid;
            }
        }

        $idx = 0;
        for ($i = 1; $i < $capacity; $i++) {
            $this->vehicleSeats[$i] = $this->buildSeatRow($i, $occupied[$idx] ?? null, 'passenger', false);
            $idx++;
        }
    }

    /**
     * Event z Step1 może wysłać tylko employee_id + position — zachowaj pozostałe klucze (np. external_driver).
     */
    protected function applyVehicleSeatUpdateFromChild(array $data): void
    {
        if (! isset($data['seat_index'])) {
            return;
        }

        $seatIndex = (int) $data['seat_index'];
        if (! array_key_exists($seatIndex, $this->vehicleSeats)) {
            return;
        }

        $existing = $this->vehicleSeats[$seatIndex];

        $employeeId = array_key_exists('employee_id', $data)
            ? self::nullableIntEmployeeId($data['employee_id'])
            : self::nullableIntEmployeeId($existing['employee_id'] ?? null);

        $position = array_key_exists('position', $data)
            ? (string) $data['position']
            : (string) ($existing['position'] ?? ($seatIndex === 0 ? 'driver' : 'passenger'));

        if ($seatIndex === 0) {
            if ($employeeId !== null) {
                $this->vehicleSeats[$seatIndex] = $this->buildSeatRow(0, $employeeId, 'driver', false);

                return;
            }

            $external = array_key_exists('external_driver', $data)
                ? (bool) $data['external_driver']
                : (bool) ($existing['external_driver'] ?? true);

            $this->vehicleSeats[$seatIndex] = $this->buildSeatRow(0, null, 'driver', $external);

            return;
        }

        $safePosition = $position === 'driver' ? 'passenger' : $position;

        $this->vehicleSeats[$seatIndex] = $this->buildSeatRow($seatIndex, $employeeId, $safePosition, false);
    }

    private static function nullableIntEmployeeId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
