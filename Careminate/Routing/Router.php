<?php declare(strict_types=1);

namespace Careminate\Routing;

use Closure;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Routing\Contracts\RouterInterface;

class Router implements RouterInterface
{
    protected static array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    public static function add(string $method, string $uri, callable|array $action): void
    {
        $method = strtoupper($method);
        self::$routes[$method][$uri] = $action;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getPathInfo();

        $routes = self::$routes[$method] ?? [];

        foreach ($routes as $route => $action) {
            if ($route === $uri) {
                return $this->callAction($action, $request);
            }
        }

        return new Response("Route [$method $uri] not found", 404);
    }

    protected function callAction(callable|array $action, Request $request): Response
    {
        if ($action instanceof Closure) {
            $result = $action($request);
        } elseif (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;

            if (is_string($controller)) {
                $controller = new $controller();
            }

            $result = $controller->$method($request);
        } else {
            throw new \RuntimeException('Invalid route action.');
        }

        if ($result instanceof Response) {
            return $result;
        }

        return new Response((string) $result);
    }
}

