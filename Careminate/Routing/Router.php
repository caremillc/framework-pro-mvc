<?php declare (strict_types = 1);
namespace Careminate\Routing;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Controllers\AbstractController;


class Router implements RouterInterface
{
    public function dispatch(Request $request, ContainerInterface $container): array
    {
        $routeHandler     = $request->getRouteHandler();
        $routeHandlerArgs = $request->getRouteHandlerArgs();

        if (is_array($routeHandler)) {
            [$controllerId, $method] = $routeHandler;
            $controller = $container->get($controllerId);
            if (is_subclass_of($controller, AbstractController::class)) {
                $controller->setRequest($request);
            }
            $routeHandler = [$controller, $method];
        }

        return [$routeHandler, $routeHandlerArgs];
    }
}