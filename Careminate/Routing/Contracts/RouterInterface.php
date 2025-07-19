<?php declare (strict_types = 1);
namespace Careminate\Routing\Contracts;

interface RouterInterface
{
    public static function add(string $method, string $route, $controller, $action, array $middleware = []);
    public function routes();
    public static function dispatch($uri, $method);
    public function setRoutes(array $routes): void;

}