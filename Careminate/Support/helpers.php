<?php declare(strict_types=1);

// Just include the file at the top of your script
require_once 'debug.php';


if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}