<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class AuditLogger
{
    public static function log(
        Model $model,
        string $event,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        $class = $model::class;

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'auditable_type' => $class,
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => self::truncateUserAgent(request()?->userAgent()),
        ]);
    }

    /**
     * Stan atrybutów po zapisie / przy tworzeniu (bieżące wartości w modelu).
     *
     * @return array<string, mixed>
     */
    public static function snapshot(Model $model): array
    {
        $out = [];
        foreach ($model->getAttributes() as $key => $value) {
            $out[$key] = self::normalizeValue($value, $model, $key);
        }

        return $out;
    }

    /**
     * Stan sprzed aktualizacji — dla każdego atrybutu wartość „sprzed zmiany” ({@see Model::getOriginal}),
     * z tymi samymi kluczami co w getAttributes() w momencie {@see Model::updating}.
     * Dzięki temu „przed” w logu audytu różni się od „po” przy zmianie statusu itd.
     *
     * @return array<string, mixed>
     */
    public static function snapshotBeforeUpdate(Model $model): array
    {
        $out = [];
        foreach (array_keys($model->getAttributes()) as $key) {
            $out[$key] = self::normalizeValue($model->getOriginal($key), $model, $key);
        }

        return $out;
    }

    private static function truncateUserAgent(?string $ua): ?string
    {
        if ($ua === null) {
            return null;
        }

        return mb_substr($ua, 0, 2000);
    }

    private static function normalizeValue(mixed $value, Model $model, string $key): mixed
    {
        if ($value === null) {
            return null;
        }

        $casts = $model->getCasts();

        if (array_key_exists($key, $casts)) {
            $cast = $casts[$key];
            if ($cast === 'datetime' || $cast === 'immutable_datetime' || str_starts_with((string) $cast, 'datetime:')) {
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d H:i:s');
                }
            }
            if ($cast === 'date' || $cast === 'immutable_date') {
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d');
                }
            }
            if (enum_exists($cast)) {
                if ($value instanceof \BackedEnum) {
                    return $value->value;
                }
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value) && ! $value instanceof \Stringable) {
            return (string) json_encode($value);
        }

        return $value;
    }
}
