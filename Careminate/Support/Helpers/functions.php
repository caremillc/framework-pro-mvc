<?php declare(strict_types=1);

use Careminate\Support\Collection;

/**
 * ================================
 * HELPER FUNCTION
 * ================================ */
if (!function_exists('collect')) {
    function collect(mixed $items = []): Collection
    {
        if (!is_array($items)) $items = [$items];
        return new Collection($items);
    }
}
