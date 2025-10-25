<?php declare(strict_types=1);

namespace Careminate\Support;

class Config
{
    protected static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $file = static::getFileFromKey($key);

        if (!isset(static::$cache[$file])) {
            $path = BASE_PATH . '/config/' . $file . '.php';
            static::$cache[$file] = file_exists($path) ? require $path : [];
        }

        return Arr::get(static::$cache[$file], static::getNestedKey($key), $default);
    }

    public static function has(string $key): bool
    {
        $file = static::getFileFromKey($key);

        if (!isset(static::$cache[$file])) {
            $path = BASE_PATH . '/config/' . $file . '.php';
            static::$cache[$file] = file_exists($path) ? require $path : [];
        }

        return Arr::has(static::$cache[$file], static::getNestedKey($key));
    }

    public static function set(string $key, mixed $value): void
    {
        $file = static::getFileFromKey($key);

        if (!isset(static::$cache[$file])) {
            $path = BASE_PATH . '/config/' . $file . '.php';
            static::$cache[$file] = file_exists($path) ? require $path : [];
        }

        Arr::set(static::$cache[$file], static::getNestedKey($key), $value);
    }

    protected static function getFileFromKey(string $key): string
    {
        return explode('.', $key)[0];
    }

    protected static function getNestedKey(string $key): ?string
    {
        $parts = explode('.', $key);
        array_shift($parts);
        return $parts ? implode('.', $parts) : null;
    }
}


