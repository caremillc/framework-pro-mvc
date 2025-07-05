<?php declare (strict_types = 1);

namespace Careminate\Routing;

use Careminate\Exceptions\HttpRequestMethodException;
use Careminate\Exceptions\NotFoundException;
use Careminate\Http\Responses\Response;
use Careminate\Logs\Log;
use Careminate\Routing\Contracts\RouterInterface;
use Exception;

/**
 * Router class responsible for handling HTTP routing in the application.
 *
 * This class implements the RouterInterface and provides functionality to:
 * - Register routes for different HTTP methods
 * - Dispatch requests to appropriate controllers/actions
 * - Handle route parameters and middleware
 * - Manage public path for static assets
 */
class Router implements RouterInterface
{
    /**
     * Array of registered routes grouped by HTTP method.
     *
     * @var array<string, array>
     */
    protected static array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    protected static array $groupStack = [];

    /**
     * The public directory path where assets are served from.
     *
     * @var string
     */
    protected static string $public;

    /**
     * Sets or gets the public path for the application.
     *
     * @param string|null $bin The path to set as public directory (optional)
     * @return string The current public path
     */
    public static function public_path(?string $bin = null): string
    {
        if (! is_null($bin)) {
            static::$public = trim($bin, '/');
        }
        return static::$public ?? 'public';
    }

    /**
     * Adds a new route to the router.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $route The URI pattern to match
     * @param mixed $controller Either a class name or a Closure
     * @param string|null $action The method name if controller is a class
     * @param array $middleware Array of middleware to apply
     * @return void
     */
    public static function add(string $method, string $route, $controller, $action = null, array $middleware = []): void
    {
        // Normalize the route by trimming slashes
        // $route = trim($route, '/');

        [$route, $middleware] = static::mergeGroupAttributes($route, $middleware);

        // Store the route with its associated controller, action and middleware
        static::$routes[strtoupper($method)][$route] = compact('controller', 'action', 'middleware');
    }

    /**
     * Returns all registered routes.
     *
     * @return array The complete routes array
     */
    public function routes(): array
    {
        return static::$routes;
    }

    /**
     * Dispatches the request to the appropriate route handler.
     *
     * @param string $uri The request URI
     * @param string $method The HTTP method
     * @return Response The response object
     * @throws Exception When route or controller/method not found
     */
    public static function dispatch($uri, $method): Response
    {
        // Handle favicon requests with empty response
        if ($uri === '/favicon.ico') {
            return new Response('', 204);
        }

        // Extract path from URI and remove public directory prefix
        $path = parse_url($uri, PHP_URL_PATH);
        $uri  = trim(str_replace(static::public_path(), '', $path), '/');

        // Check all routes for the current HTTP method
        foreach (static::$routes[$method] as $route => $routeData) {
            // Convert route parameters to regex pattern
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $route);
            $pattern = "#^$pattern$#";

            if (preg_match($pattern, $uri, $matches)) {
                // Extract named parameters from matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $controller = $routeData['controller'];
                $action     = $routeData['action'] ?? null;

                // Handle closure routes
                if ($controller instanceof \Closure) {
                    ob_start();
                    $returned = call_user_func_array($controller, $params);
                    $output   = ob_get_clean();

                    if ($returned instanceof Response) {
                        return $returned;
                    }

                    $content = $returned !== null ? $returned : $output;
                    return new Response($content, 200);
                }

                // Handle controller class routes
                if (! class_exists($controller)) {
                    throw new NotFoundException("Controller class {$controller} not found");
                }

                $controllerInstance = new $controller();

                if (! method_exists($controllerInstance, $action)) {
                    throw new HttpRequestMethodException("Method {$action} not found in controller {$controller}");
                }

                ob_start();
                $returned = call_user_func_array([$controllerInstance, $action], $params);
                $output   = ob_get_clean();

                if ($returned instanceof Response) {
                    return $returned;
                }

                $content = $returned !== null ? $returned : $output;

                return new Response($content, 200);
            }
        }

        // No matching route found
        throw new Log("Route '{$uri}' not found.", Response::HTTP_NOT_FOUND);
    }

    public static function group(array $attributes): void
    {
        static::$groupStack[] = $attributes;
    }

    public static function startGroup(array $attributes): void
    {
        static::$groupStack[] = $attributes;
    }

    public static function endGroup(): void
    {
        array_pop(static::$groupStack);
    }

    protected static function mergeGroupAttributes(string $route, array $middleware): array
    {
        $prefix          = '';
        $groupMiddleware = [];

        foreach (static::$groupStack as $group) {
            if (! empty($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (! empty($group['middleware'])) {
                $groupMiddleware = array_merge($groupMiddleware, $group['middleware']);
            }
        }

        $fullRoute        = trim($prefix . '/' . trim($route, '/'), '/');
        $mergedMiddleware = array_merge($groupMiddleware, $middleware);

        return [$fullRoute, $mergedMiddleware];
    }

}
