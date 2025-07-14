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

}
