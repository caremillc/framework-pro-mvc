<?php declare(strict_types=1);

namespace Careminate\Support;

use BadMethodCallException;

/**
 * ================================
 * MACROABLE TRAIT
 * ================================
 */
trait Macroable
{
    protected static array $macros = [];

    public static function macro(string $name, callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public function __call(string $method, array $parameters)
    {
        if (!isset(static::$macros[$method])) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        // Bind the macro closure to $this instance and call
        return call_user_func_array(
            static::$macros[$method]->bindTo($this, static::class),
            $parameters
        );
    }

    public static function __callStatic(string $method, array $parameters)
    {
        if (!isset(static::$macros[$method])) {
            throw new BadMethodCallException("Static method {$method} does not exist.");
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }
}


