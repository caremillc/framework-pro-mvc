<?php declare (strict_types = 1);
namespace Careminate\Routing;

use Careminate\Routing\Traits\Methods;

class Route extends Router
{
    use Methods;

    public static function prefix(string $prefix): PendingRouteGroup
    {
        return new \Careminate\Routing\PendingRouteGroup(['prefix' => $prefix]);
    }

    public static function middleware(array $middleware): PendingRouteGroup
    {
        return new \Careminate\Routing\PendingRouteGroup(['middleware' => $middleware]);
    }

    public static function resource(string $name, string $controller): PendingResourceRegistration
    {
        $pending = new PendingResourceRegistration($name, $controller);
        $pending->register();
        return $pending;
    }
}
