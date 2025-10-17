<?php declare (strict_types = 1);
namespace Careminate\Routing;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Routing\Route;

class Router
{
    protected array $routes      = [];
    protected array $namedRoutes = [];

    public function get(string $uri, $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function addRoute(string $method, string $uri, $action): Route
    {
        $route          = new Route($method, $uri, $action);
        $this->routes[] = $route;
        return $route;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri    = $request->getPathInfo();

        foreach ($this->routes as $route) {
            $pattern = preg_replace('#\{([^}]+)\}#', '([^/]+)', $route->uri);
            $pattern = "#^" . rtrim($pattern, '/') . "/?$#";

            if ($route->method === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // remove full match
                $params = $matches;
                return $this->handleMatchedRoute($route, $request, $params);
            }
        }

        return new Response('404 Not Found', 404);
    }

    protected function handleMatchedRoute(Route $route, Request $request, array $params): Response
    {
        // Run middleware (if any)
        foreach ($route->middleware as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass;
                if (method_exists($middleware, 'handle')) {
                    $response = $middleware->handle($request);
                    if ($response instanceof Response) {
                        return $response; // stop the chain
                    }
                }
            }
        }

        // Execute route action
        if (is_callable($route->action)) {
            return new Response((string) call_user_func_array($route->action, array_merge([$request], $params)));
        }

        if (is_string($route->action)) {
            [$controller, $method] = explode('@', $route->action);
            $controller            = "App\\Http\\Controllers\\{$controller}";
            $instance              = new $controller;
            return new Response((string) call_user_func_array([$instance, $method], array_merge([$request], $params)));
        }

        return new Response('Invalid route action.', 500);
    }

    // Named routes
    public function name(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    public function routeUrl(string $name, array $params = []): ?string
    {
        if (! isset($this->namedRoutes[$name])) {
            return null;
        }

        $uri = $this->namedRoutes[$name]->uri;

        foreach ($params as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
        }

        return $uri;
    }
}
