<?php 

use Careminate\Support\Collection;
use Careminate\Http\Requests\Request;

// Just include the file at the top of your script
require_once 'debug_functions.php';

/**
 * ================================
 * Start Collection Class
 * ================================ 
 * */
if (!function_exists('collect')) {
    function collect(mixed $items = []): Collection
    {
        if (!is_array($items)) $items = [$items];
        return new Collection($items);
    }
}

/**
 * ================================
 * End Collection Class
 * ================================ 
 * */


/**
 * ================================
 * Start Request Class
 * ================================ 
 * */
if (!function_exists('value')) {
    /**
     * Return the default value of a variable or call it if Closure
     */
    function value(mixed $value): mixed
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}
if (!function_exists('request')) {
    /**
     * Get the current Request instance or a specific input value.
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function request(string|array|null $key = null, mixed $default = null): mixed
    {
        static $instance = null;

        if ($instance === null) {
            $instance = Request::createFromGlobals();
        }

        if (is_string($key)) {
            return $instance->input($key, $default);
        }

        if (is_array($key)) {
            return $instance->only($key);
        }

        return $instance;
    }
}

/**
 * Shortcut: Get only specified input keys.
 *
 * @param array|string ...$keys
 * @return array
 */
if (!function_exists('request_only')) {
    function request_only(array|string ...$keys): array
    {
        return request()->only(...$keys);
    }
}

/**
 * Shortcut: Get all input except specified keys.
 *
 * @param array|string ...$keys
 * @return array
 */
if (!function_exists('request_except')) {
    function request_except(array|string ...$keys): array
    {
        return request()->except(...$keys);
    }
}

/**
 * Shortcut: Get all input data (GET + POST + JSON + raw input merged)
 *
 * @return array
 */
if (!function_exists('request_all')) {
    function request_all(): array
    {
        return request()->all();
    }
}

/**
 * Shortcut: Get JSON payload as array.
 *
 * @return array
 */
if (!function_exists('request_json')) {
    function request_json(): array
    {
        return request()->json();
    }
}

/**
 * Shortcut: Check if a key exists in input.
 *
 * @param string $key
 * @return bool
 */
if (!function_exists('request_has')) {
    function request_has(string $key): bool
    {
        return request()->has($key);
    }
}

/**
 * Shortcut: Get a cookie value.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('request_cookie')) {
    function request_cookie(string $key, mixed $default = null): mixed
    {
        return request()->cookie($key, $default);
    }
}

/**
 * Shortcut: Get a header value.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('request_header')) {
    function request_header(string $key, mixed $default = null): mixed
    {
        return request()->header($key) ?? $default;
    }
}

if (!function_exists('data_get')) {
    /**
     * Get a value from an array or object using dot notation
     */
    function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) return $target;

        $key = (string)$key;
        if (is_array($target) && array_key_exists($key, $target)) {
            return $target[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('data_set')) {
    /**
     * Set a value in an array or object using dot notation
     */
    function data_set(mixed &$target, string|int $key, mixed $value): void
    {
        $keys = explode('.', (string)$key);

        while (count($keys) > 1) {
            $segment = array_shift($keys);

            if (is_array($target)) {
                if (!isset($target[$segment]) || !is_array($target[$segment])) {
                    $target[$segment] = [];
                }
                $target = &$target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment}) || !is_object($target->{$segment})) {
                    $target->{$segment} = new \stdClass();
                }
                $target = &$target->{$segment};
            } else {
                throw new \RuntimeException("Cannot set key on non-array/object.");
            }
        }

        $last = array_shift($keys);
        if (is_array($target)) $target[$last] = $value;
        elseif (is_object($target)) $target->{$last} = $value;
    }
}


/**
 * ================================
 * End Request Class
 * ================================ 
 * */
