<?php

use App\Support\Calendar\Layers\AccommodationAssignmentLayer;
use App\Support\Calendar\Layers\AccommodationLeaseLayer;
use App\Support\Calendar\Layers\DepartureLayer;
use App\Support\Calendar\Layers\ProjectAssignmentLayer;
use App\Support\Calendar\Layers\ReturnTripLayer;
use App\Support\Calendar\Layers\RotationLayer;
use App\Support\Calendar\Layers\SprintLayer;
use App\Support\Calendar\Layers\TaskLayer;
use App\Support\Calendar\Layers\TransferLayer;
use App\Support\Calendar\Layers\VehicleAssignmentLayer;
use App\Support\Calendar\Layers\VehicleServiceLayer;

return [

    /*
    |--------------------------------------------------------------------------
    | Warstwy kalendarza (/kalendarz)
    |--------------------------------------------------------------------------
    |
    | Każda pozycja to klasa dziedzicząca po App\Support\Calendar\CalendarLayer.
    | Warstwa pojawia się automatycznie jako przełącznik w panelu filtrów —
    | żeby dodać nowy typ zdarzeń (np. spotkania), wystarczy nowa klasa tutaj.
    |
    */

    'layers' => [
        TaskLayer::class,
        SprintLayer::class,

        DepartureLayer::class,
        ReturnTripLayer::class,
        TransferLayer::class,

        VehicleAssignmentLayer::class,
        VehicleServiceLayer::class,

        AccommodationAssignmentLayer::class,
        AccommodationLeaseLayer::class,

        RotationLayer::class,
        ProjectAssignmentLayer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Kolejność grup w panelu filtrów
    |--------------------------------------------------------------------------
    |
    | Grupy spoza tej listy trafiają na koniec, w kolejności rejestracji warstw.
    |
    */

    'groups' => [
        'Zadania',
        'Logistyka',
        'Pojazdy',
        'Zakwaterowanie',
        'Pracownicy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Limity
    |--------------------------------------------------------------------------
    |
    | max_events_per_layer — bezpiecznik na zapytania (widok miesiąca potrafi
    | objąć 6 tygodni × 11 warstw).
    | max_lanes — ile pasków zmieści się w wierszu tygodnia (widok miesiąca)
    | zanim reszta zwinie się do licznika „+N więcej”. Widok tygodnia nie ma limitu.
    |
    */

    'max_events_per_layer' => 400,

    'max_lanes' => 4,

];
