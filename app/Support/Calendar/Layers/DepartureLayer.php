<?php

namespace App\Support\Calendar\Layers;

use App\Enums\LogisticsEventType;

class DepartureLayer extends LogisticsEventLayer
{
    public function key(): string
    {
        return 'departures';
    }

    public function label(): string
    {
        return 'Wyjazdy';
    }

    public function icon(): string
    {
        return 'bi bi-box-arrow-right';
    }

    public function color(): string
    {
        return '#a855f7';
    }

    public function permission(): ?string
    {
        return 'departures.view';
    }

    public function description(): ?string
    {
        return 'Bez zdarzeń anulowanych';
    }

    protected function type(): LogisticsEventType
    {
        return LogisticsEventType::DEPARTURE;
    }

    protected function routeName(): string
    {
        return 'departures.show';
    }
}
