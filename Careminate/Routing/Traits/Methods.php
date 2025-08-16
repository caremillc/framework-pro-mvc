<?php declare(strict_types = 1);
namespace Careminate\Routing\Traits;

trait Methods
{
    public static function get(string $route, $controller, $action = null, array $middleware = []): void
    {
        static::add('GET', $route, $controller, $action, $middleware);
    }

    public static function post(string $route, $controller, $action, array $middleware = []): void
    {
        static::add('POST', $route, $controller, $action, $middleware);
    }

    public static function put(string $route, $controller, $action, array $middleware = []): void
    {
        static::add('PUT', $route, $controller, $action, $middleware);
    }

    public static function patch(string $route, $controller, $action, array $middleware = []): void
    {
        static::add('PATCH', $route, $controller, $action, $middleware);
    }

    public static function delete(string $route, $controller, $action, array $middleware = []): void
    {
        static::add('DELETE', $route, $controller, $action, $middleware);
    }
}
