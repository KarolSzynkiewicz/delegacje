<?php

namespace App\Data;

/**
 * Konfiguracja jednego odcinka ziemnego (1 slot = 1 środek transportu).
 *
 * Nie zapisuje się samodzielnie do bazy — służy jako kontrakt danych
 * między komponentami UI a warstwą zapisu (TransferService, DepartureController).
 *
 * legKind:   'own' | 'public' | null
 * groundMode: 'car' | 'other' | null  (tylko gdy legKind === 'own')
 */
class TransferGroundConfig
{
    public function __construct(
        public ?int $vehicleId = null,
        public ?int $driverEmployeeId = null,
        public ?float $driverPaymentAmount = null,
        public string $driverPaymentCurrency = 'PLN',
        public ?int $driverPayrollId = null,
        /** @var list<string> klucze 'loc:<id>' lub 'acc:<id>' */
        public array $routeWaypoints = [],
        /** @var array<string, string> location_id (string) => notatka */
        public array $locationStopNotes = [],
        public ?float $routeDistance = null,
        public ?int $routeDuration = null,
        public bool $routeDistanceIsManual = false,
        public ?int $endAirportLocationId = null,
        /** 'own' | 'public' | null */
        public ?string $legKind = null,
        /** 'car' | 'other' | null */
        public ?string $groundMode = null,
        /** @var array<int|string, array<string, mixed>> employee_id => ['amount' => float, 'currency' => string, 'attachment_path' => ?string] */
        public array $publicTicketCostsByEmployee = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            vehicleId: isset($data['vehicle_id']) && $data['vehicle_id'] !== '' ? (int) $data['vehicle_id'] : null,
            driverEmployeeId: isset($data['driver_employee_id']) && $data['driver_employee_id'] !== '' ? (int) $data['driver_employee_id'] : null,
            driverPaymentAmount: isset($data['driver_payment_amount']) && is_numeric($data['driver_payment_amount']) ? (float) $data['driver_payment_amount'] : null,
            driverPaymentCurrency: strtoupper(trim((string) ($data['driver_payment_currency'] ?? 'PLN'))) ?: 'PLN',
            driverPayrollId: isset($data['driver_payroll_id']) && $data['driver_payroll_id'] !== '' ? (int) $data['driver_payroll_id'] : null,
            routeWaypoints: is_array($data['route_waypoints'] ?? null) ? array_values($data['route_waypoints']) : [],
            locationStopNotes: is_array($data['location_stop_notes'] ?? null) ? $data['location_stop_notes'] : [],
            routeDistance: isset($data['route_distance']) && is_numeric($data['route_distance']) ? (float) $data['route_distance'] : null,
            routeDuration: isset($data['route_duration']) && is_numeric($data['route_duration']) ? (int) $data['route_duration'] : null,
            routeDistanceIsManual: (bool) ($data['route_distance_is_manual'] ?? false),
            endAirportLocationId: isset($data['end_airport_location_id']) && $data['end_airport_location_id'] !== '' ? (int) $data['end_airport_location_id'] : null,
            legKind: isset($data['leg_kind']) && in_array($data['leg_kind'], ['own', 'public'], true) ? $data['leg_kind'] : null,
            groundMode: isset($data['ground_mode']) && in_array($data['ground_mode'], ['car', 'other'], true) ? $data['ground_mode'] : null,
            publicTicketCostsByEmployee: is_array($data['public_ticket_costs_by_employee'] ?? null) ? $data['public_ticket_costs_by_employee'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'vehicle_id' => $this->vehicleId,
            'driver_employee_id' => $this->driverEmployeeId,
            'driver_payment_amount' => $this->driverPaymentAmount,
            'driver_payment_currency' => $this->driverPaymentCurrency,
            'driver_payroll_id' => $this->driverPayrollId,
            'route_waypoints' => $this->routeWaypoints,
            'location_stop_notes' => $this->locationStopNotes,
            'route_distance' => $this->routeDistance,
            'route_duration' => $this->routeDuration,
            'route_distance_is_manual' => $this->routeDistanceIsManual,
            'end_airport_location_id' => $this->endAirportLocationId,
            'leg_kind' => $this->legKind,
            'ground_mode' => $this->groundMode,
            'public_ticket_costs_by_employee' => $this->publicTicketCostsByEmployee,
        ];
    }

    /**
     * Slot pusty = nie skonfigurowano żadnego środka transportu.
     */
    public function isEmpty(): bool
    {
        return $this->legKind === null
            && $this->vehicleId === null
            && $this->routeWaypoints === []
            && $this->publicTicketCostsByEmployee === [];
    }
}
