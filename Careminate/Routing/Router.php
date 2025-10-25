<?php declare (strict_types = 1);
namespace Careminate\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Exceptions\HttpException;
use function FastRoute\simpleDispatcher;
use Careminate\Exceptions\HttpRequestMethodException;

class Router implements RouterInterface
{
     private array $routes;
    
    public function setRoutes(array $routes): void
    {
        //$routes is parsed from setRoutes in $container
        $this->routes = $routes;
    }

   public function dispatch(Request $request, ContainerInterface $container): array
	{
		$routeInfo = $this->extractRouteInfo($request);

		if ($routeInfo === null) {
			return [fn() => new \Careminate\Http\Responses\Response('', 204), []];
		}

		[$handler, $vars] = $routeInfo;

		if (is_callable($handler)) {
			return [$handler, $vars];
		}

		if (! is_array($handler) || ! is_string($handler[0]) || ! is_string($handler[1])) {
			throw new \InvalidArgumentException('Invalid route handler definition.');
		}

		[$controllerId, $method] = $handler;
		$controller = $container->get($controllerId);

		return [[$controller, $method], $vars];
	}


    private function extractRouteInfo(Request $request): array | null
    {
        $requestedPath = $request->getPathInfo();

        if ($requestedPath === '/favicon.ico') {
            return null; // gracefully handled above
        }

        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            // $routes = require_once route_path('web.php');
            foreach ($this->routes as $route) {
                $routeCollector->addRoute(...$route);
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $requestedPath
        );

        // switch ($routeInfo[0]) {
        //     case Dispatcher::FOUND:
        //         return [$routeInfo[1], $routeInfo[2]];
        //     case Dispatcher::METHOD_NOT_ALLOWED:
        //         $allowedMethods = implode(',', $routeInfo[1]);
        //         throw new HttpRequestMethodException("The allowed methods are $allowedMethods", 405);
        //     default:
        //     //IF THE ROUTE IS NOT FOUND
        //         throw new HttpException('Not found', 404);
        // }
        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                return [$routeInfo[1], $routeInfo[2]]; // routeHandler, vars
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = implode(', ', $routeInfo[1]);
                $e = new HttpRequestMethodException("The allowed methods are $allowedMethods");
                $e->setStatusCode(405);
                throw $e;
            default:
                $e = new HttpException('Not found');
                $e->setStatusCode(404);
                throw $e;
        }
    }

}
