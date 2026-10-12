<?php

namespace App\Livewire\Concerns;

use App\Support\SlotReminder;

trait TogglesSlotReminders
{
    public function toggleSlotReminder(string $key): void
    {
        SlotReminder::toggle($key, auth()->user());
    }
}
