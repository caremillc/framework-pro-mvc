<?php  declare(strict_types=1);
namespace Careminate\Routing;

class Segment
{

    // public static function uri(): string
    // {
    //     return str_replace(ROOT_DIR, '', parse_url($_SERVER['REQUEST_URI'])['path']);
    // }
    public static function uri(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        return trim(str_replace(ROOT_DIR, '', $path), '/');
    }
    /**
     * @param int $offset
     *
     * @return string
     */
    public static function get(int $offset): string
    {
        $uri      = static::uri();
        $segments = explode('/', $uri);
        return isset($segments[$offset]) ? $segments[$offset] : '';
    }

    // public static function all(): array
    // {
    //     return explode('/', static::uri());
    // }

    public static function all(): array
    {
        return array_values(array_filter(explode('/', static::uri())));
    }

}
