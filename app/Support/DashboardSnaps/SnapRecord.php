<?php

namespace App\Support\DashboardSnaps;

use Closure;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Lekki obiekt do podglądów dashboardu: wygląda jak model w Blade
 * (`$row->name`, `route('x.show', $row)`), ale nic nie zapisuje w bazie.
 */
final class SnapRecord implements UrlRoutable
{
    public function __construct(private array $attributes = []) {}

    public function __get(string $key): mixed
    {
        if (! array_key_exists($key, $this->attributes)) {
            return null;
        }

        $value = $this->attributes[$key];

        return $value instanceof Closure ? $value($this) : $value;
    }

    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes) && $this->attributes[$key] !== null;
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (isset($this->attributes[$name]) && $this->attributes[$name] instanceof Closure) {
            return ($this->attributes[$name])(...$arguments);
        }

        throw new \BadMethodCallException(sprintf('%s::%s() is not defined.', self::class, $name));
    }

    public function getRouteKey(): mixed
    {
        return $this->attributes['id'] ?? 0;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return null;
    }

    public function resolveChildRouteBinding($childType, $value, $field): ?Model
    {
        return null;
    }
}
