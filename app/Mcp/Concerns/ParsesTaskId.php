<?php

namespace App\Mcp\Concerns;

trait ParsesTaskId
{
    protected function parseTaskId(mixed $value): ?int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        if (is_string($value) && preg_match('/^#(\d+)$/', trim($value), $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
