<?php

namespace App\Support\Calendar\Layers;

use App\Enums\LogisticsEventType;

class ReturnTripLayer extends LogisticsEventLayer
{
    public function key(): string
    {
        return 'return_trips';
    }

    public function label(): string
    {
        return 'Zjazdy';
    }

    public function icon(): string
    {
        return 'bi bi-box-arrow-in-left';
    }

    public function color(): string
    {
        return '#8b5cf6';
    }

    public function permission(): ?string
    {
        return 'return-trips.view';
    }

    public function description(): ?string
    {
        return 'Bez zdarzeń anulowanych';
    }

    protected function type(): LogisticsEventType
    {
        return LogisticsEventType::RETURN;
    }

    protected function routeName(): string
    {
        return 'return-trips.show';
    }
}
