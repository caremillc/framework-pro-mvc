<?php declare(strict_types=1);

namespace Careminate\Support;

use ArrayAccess;
use InvalidArgumentException;

/**
 * ================================
 * ARRAY UTILITIES
 * ================================
 */
class Arr
{
      public static function add(array $array, string|int $key, mixed $value): array
    {
        if (static::get($array, $key) === null) static::set($array, $key, $value);
        return $array;
    }

    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

 public static function exists(array|ArrayAccess $array, string|int $key): bool
    {
        return $array instanceof ArrayAccess ? $array->offsetExists($key) : array_key_exists($key, $array);
    }
       public static function get(array|ArrayAccess $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) return $array;
        $keys = explode('.', (string)$key);
        foreach ($keys as $segment) {
            $found = false;
            if ($array instanceof ArrayAccess) {
                if ($array->offsetExists($segment)) { $array = $array[$segment]; $found = true; }
            } elseif (is_array($array)) {
                if (array_key_exists($segment, $array)) { $array = $array[$segment]; $found = true; }
            }
            if (!$found) return $default instanceof \Closure ? $default() : $default;
        }
        return $array;
    }


    public static function set(array &$array, string|int $key, mixed $value): void
    {
        $keys = explode('.', (string)$key);
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($array[$segment]) || !is_array($array[$segment])) $array[$segment] = [];
            $array = &$array[$segment];
        }
        $array[array_shift($keys)] = $value;
    }

   public static function has(array $array, string|int $key): bool
    {
        foreach (explode('.', (string)$key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) return false;
            $array = $array[$segment];
        }
        return true;
    }

     public static function forget(array &$array, string|int $key): void
    {
        $keys = explode('.', (string)$key);
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($array[$segment]) || !is_array($array[$segment])) return;
            $array = &$array[$segment];
        }
        unset($array[array_shift($keys)]);
    }


   public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) return empty($array) ? ($default instanceof \Closure ? $default() : $default) : reset($array);
        foreach ($array as $k => $v) if ($callback($v, $k)) return $v;
        return $default instanceof \Closure ? $default() : $default;
    }
     public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        return static::first(array_reverse($array, true), $callback, $default);
    }

    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $result = [];
        foreach ($array as $item) {
            if (!is_array($item)) $result[] = $item;
            elseif ($depth === 1) $result = array_merge($result, array_values($item));
            else $result = array_merge($result, static::flatten($item, $depth - 1));
        }
        return $result;
    }
    public static function pluck(array $array, string $value, ?string $key = null): array
    {
        $results = [];
        foreach ($array as $item) {
            $v = is_array($item) ? ($item[$value] ?? null) : ($item->{$value} ?? null);
            if ($key === null) $results[] = $v;
            else $results[is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null)] = $v;
        }
        return $results;
    }

    public static function map(array $array, callable $callback): array
    {
        return array_map($callback, $array, array_keys($array));
    }

    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function random(array $array, int $number = 1): mixed
    {
        if ($number < 1) throw new InvalidArgumentException("Number must be >= 1");
        $keys = array_rand($array, $number);
        return $number === 1 ? $array[$keys] : array_intersect_key($array, array_flip((array)$keys));
    }

    public static function shuffle(array $array): array
    {
        shuffle($array);
        return $array;
    }

    public static function collapse(array $array): array
    {
        $results = [];
        foreach ($array as $values) if (is_array($values)) $results = array_merge($results, $values);
        return $results;
    }

    public static function pull(array &$array, string|int $key, mixed $default = null): mixed
    {
        $value = static::get($array, $key, $default);
        static::forget($array, $key);
        return $value;
    }

    public static function wrap(mixed $value): array
    {
        if ($value === null) return [];
        return is_array($value) ? $value : [$value];
    }
       public static function accessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }
      public static function except(array $array, array|string $keys): array
    {
        foreach ((array)$keys as $key) static::forget($array, $key);
        return $array;
    }
    
}
