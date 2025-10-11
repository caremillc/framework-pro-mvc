<?php declare (strict_types = 1);
namespace Careminate\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Exceptions\HttpException;
use function FastRoute\simpleDispatcher;
use Careminate\Routing\Contracts\RouterInterface;
use Careminate\Exceptions\HttpRequestMethodException;

class Router implements RouterInterface
{
    private array $routes = [];

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }
    public function dispatch(Request $request, ContainerInterface $container): array
    {
        $routeInfo = $this->extractRouteInfo($request);

        // If routeInfo is null (favicon.ico), short-circuit gracefully
        if ($routeInfo === null) {
            return [fn() => new \Careminate\Http\Responses\Response('', 204), []];
        }

        [$handler, $vars] = $routeInfo;

        // Case 1: Closure handler
        // if ($handler instanceof \Closure) {
        //     return [$handler, $vars];
        // }
          if ($handler instanceof \Closure) {
            return [[$handler, '__invoke'], $vars];
        }

        // Case 2: Single-action controller [Controller::class]
        // if (is_array($handler) && count($handler) === 1 && is_string($handler[0])) {
        //     $controller = new $handler[0];
        //     return [[$controller, '__invoke'], $vars];
        // }
           // 🔹 Case 2: [Controller::class, 'method']
          if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            [$controller, $method] = $handler;

            // Use the container to resolve the controller (with dependencies)
            $controllerInstance = $container->get($controller);

            return [[$controllerInstance, $method], $vars];
        }

        // Case 3: Normal controller [Controller::class, 'method']
        if (is_array($handler) && is_string($handler[0]) && is_string($handler[1])) {
            [$controller, $method] = $handler;
            return [[new $controller, $method], $vars];
        }

        throw new \InvalidArgumentException('Invalid route handler definition.');
    }

    private function extractRouteInfo(Request $request): array | null
    {
        $requestedPath = $request->getPathInfo();

        if ($requestedPath === '/favicon.ico') {
            return null; // gracefully handled above
        }

        // Load routes (ensures web.php executes once)
        // require_once route_path('web.php');

       $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            foreach ($this->routes as $method => $routes) {
                foreach ($routes as $route) {
                    $routeCollector->addRoute($method, $route['path'], $route['handler']);
                }
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $requestedPath
        );

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                return [$routeInfo[1], $routeInfo[2]];
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = implode(', ', $routeInfo[1]);
                throw new HttpRequestMethodException("The allowed methods are $allowedMethods", 405);
            default:
                throw new HttpException('Not found', 404);
        }
    }

}
