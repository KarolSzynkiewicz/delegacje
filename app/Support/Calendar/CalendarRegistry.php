<?php

namespace App\Support\Calendar;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Rejestr warstw kalendarza zbudowany z `config('calendar.layers')`.
 */
class CalendarRegistry
{
    /** @var Collection<string, CalendarLayer>|null */
    protected ?Collection $layers = null;

    /**
     * Wszystkie zarejestrowane warstwy, w kolejności grup z konfiguracji.
     *
     * @return Collection<string, CalendarLayer>
     */
    public function all(): Collection
    {
        if ($this->layers !== null) {
            return $this->layers;
        }

        $groupOrder = array_flip(config('calendar.groups', []));

        $layers = collect(config('calendar.layers', []))
            ->map(fn (string $class) => app($class))
            ->filter(fn ($layer) => $layer instanceof CalendarLayer)
            ->values()
            ->sortBy(fn (CalendarLayer $layer) => $groupOrder[$layer->group()] ?? PHP_INT_MAX)
            ->keyBy(fn (CalendarLayer $layer) => $layer->key());

        return $this->layers = $layers;
    }

    /**
     * Warstwy, do których użytkownik ma uprawnienia.
     *
     * @return Collection<string, CalendarLayer>
     */
    public function visibleFor(?User $user): Collection
    {
        return $this->all()->filter(function (CalendarLayer $layer) use ($user) {
            $permission = $layer->permission();

            if ($permission === null) {
                return true;
            }

            return $user !== null && $user->hasPermission($permission);
        });
    }

    public function find(string $key): ?CalendarLayer
    {
        return $this->all()->get($key);
    }
}
