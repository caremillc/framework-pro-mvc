<?php declare (strict_types = 1);

namespace Careminate\Support\Traits;

use BadMethodCallException;
use Closure;

trait Macroable
{
    protected static array $macros = [];

    public static function macro(string $name, Closure $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public function __call(string $method, array $args)
    {
        // First check if method exists in the class
        if (method_exists($this, $method)) {
            return $this->$method(...$args);
        }

        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            return call_user_func_array($macro->bindTo($this, static::class), $args);
        }

        return $macro(...$args);
    }

    public static function __callStatic(string $method, array $args)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Static method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            return call_user_func_array(Closure::bind($macro, null, static::class), $args);
        }

        return $macro(...$args);
    }
}
