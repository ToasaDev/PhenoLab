<?php

namespace App\Http\Controllers\Api\Concerns;

trait SanitizesOrdering
{
    /**
     * Parse and validate an ordering parameter against an allowlist.
     *
     * @return array{string, string} [column, direction]
     */
    protected function parseOrdering(string $orderBy, array $allowed, string $default): array
    {
        $direction = str_starts_with($orderBy, '-') ? 'desc' : 'asc';
        $column = ltrim($orderBy, '-');

        if (! in_array($column, $allowed, true)) {
            $column = $default;
            $direction = 'asc';
        }

        return [$column, $direction];
    }
}
