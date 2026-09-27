<?php

use Carbon\Carbon;

return [

    /*
    |--------------------------------------------------------------------------
    | Dzień wysyłki
    |--------------------------------------------------------------------------
    |
    | Ławka na przeglądzie tygodnia jest zdjęciem tego dnia (kto jest w bazie).
    | Stała Carbon::MONDAY … Carbon::SUNDAY. Inna firma zmienia dzień tutaj,
    | bez zmiany wzoru kafelka.
    |
    */

    'dispatch_weekday' => Carbon::SATURDAY,

];
