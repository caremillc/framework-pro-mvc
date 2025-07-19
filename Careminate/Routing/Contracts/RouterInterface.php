<?php declare (strict_types = 1);
namespace Careminate\Routing\Contracts;

use Careminate\Container\Contracts\ContainerInterface;

interface RouterInterface
{
    public static function add(string $method, string $route, $controller, $action, array $middleware = []);
    public function routes();
    // public static function dispatch($uri, $method);
    public static function dispatch($uri, $method, ContainerInterface $container);
    public function setRoutes(array $routes): void;

}