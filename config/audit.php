<?php

return [
    /*
    | Etykiety typów zapisywanych w audycie (klasa modelu => krótki opis PL).
    */
    'model_labels' => [
        \App\Models\LogisticsEvent::class => 'Zdarzenie logistyczne',
        \App\Models\VehicleAssignment::class => 'Przypisanie pojazdu',
        \App\Models\ProjectAssignment::class => 'Przypisanie do projektu',
        \App\Models\AccommodationAssignment::class => 'Przypisanie do zakwaterowania',
        \App\Models\TransportCost::class => 'Koszt transportu',
        \App\Models\Adjustment::class => 'Korekta wynagrodzenia',
        \App\Models\LogisticsEventParticipant::class => 'Uczestnik zdarzenia logistycznego',
    ],

    'event_labels' => [
        'created' => 'Utworzenie',
        'updated' => 'Zmiana',
        'deleted' => 'Usunięcie',
    ],

    /*
    | Etykiety pól (klucz atrybutu / ścieżka z flatten) — czytelniejsze nazwy w widoku diff.
    */
    'field_labels' => [
        'id' => 'ID',
        'type' => 'Typ',
        'status' => 'Status',
        'notes' => 'Notatki',
        'event_date' => 'Data zdarzenia',
        'end_date' => 'Data zakończenia',
        'from_location_id' => 'Lokalizacja od',
        'to_location_id' => 'Lokalizacja do',
        'vehicle_id' => 'Pojazd',
        'has_transport' => 'Transport',
        'has_reassignment' => 'Przeniesienie',
        'route_distance' => 'Dystans trasy',
        'route_duration' => 'Czas trasy',
        'route_waypoints' => 'Punkty pośrednie',
        'created_by' => 'Utworzone przez',
        'created_at' => 'Utworzono',
        'updated_at' => 'Zaktualizowano',
        'employee_id' => 'Pracownik',
        'project_id' => 'Projekt',
        'accommodation_id' => 'Zakwaterowanie',
        'role_id' => 'Rola',
        'start_date' => 'Data od',
        'logistics_event_id' => 'Zdarzenie logistyczne',
        'cost_type' => 'Typ kosztu',
        'amount' => 'Kwota',
        'currency' => 'Waluta',
        'cost_date' => 'Data kosztu',
        'description' => 'Opis',
        'file_path' => 'Plik',
        'transport_id' => 'Rezerwacja transportu',
        'date' => 'Data',
        'payroll_id' => 'Rozliczenie płac',
        'assignment_type' => 'Typ przypisania',
        'assignment_id' => 'ID przypisania',
        'original_end_date' => 'Pierwotna data zakończenia',
    ],
];
