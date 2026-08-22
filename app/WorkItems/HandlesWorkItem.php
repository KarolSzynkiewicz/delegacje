<?php

namespace App\WorkItems;

use App\Models\WorkItem;

interface HandlesWorkItem
{
    public function supports(GridField $field): bool;

    public function writable(GridField $field): bool;

    public function statusWidget(): StatusWidget;

    public function statusLabel(WorkItem $item): string;

    public function write(WorkItem $item, GridField $field, mixed $value): void;

    public function expandable(WorkItem $item): bool;

    /**
     * Czy wiersz można przeciągnąć między grupami tego pola (Kanban).
     * Osobno od writable(): pigułka 2-stanowa to nie to samo co drop na „W trakcie”.
     */
    public function relocatable(GridField $field): bool;
}
