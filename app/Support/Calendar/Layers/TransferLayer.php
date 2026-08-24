<?php

namespace App\Support\Calendar\Layers;

use App\Enums\LogisticsEventType;

class TransferLayer extends LogisticsEventLayer
{
    public function key(): string
    {
        return 'transfers';
    }

    public function label(): string
    {
        return 'Transfery';
    }

    public function icon(): string
    {
        return 'bi bi-arrow-left-right';
    }

    public function color(): string
    {
        return '#0ea5e9';
    }

    public function permission(): ?string
    {
        return 'transfers.view';
    }

    public function description(): ?string
    {
        return 'Bez zdarzeń anulowanych';
    }

    protected function type(): LogisticsEventType
    {
        return LogisticsEventType::TRANSFER;
    }

    protected function routeName(): string
    {
        return 'transfers.show';
    }
}
