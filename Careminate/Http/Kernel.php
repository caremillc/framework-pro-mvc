<?php declare(strict_types=1);

namespace Careminate\Http;

use ReflectionMethod;
use ReflectionFunction;
use ReflectionNamedType;
use Careminate\Routing\Router;
use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use Careminate\Routing\Contracts\RouterInterface;

class Kernel
{
    public function __construct(
        private RouterInterface $router,
        private ContainerInterface $container
    ){}

    /**
     * Handle an incoming HTTP request and return a Response.
     */
    public function handle(Request $request): Response
    {
        try {
            // Get the route handler and route variables
            // [$handler, $vars] = $this->router->dispatch($request);
            [$handler, $vars] = $this->router->dispatch($request, $this->container);  //$this->container

            if (!is_callable($handler)) {
                // If it's a controller array or class string, convert to callable
                $handler = $this->resolveHandler($handler);
            }

            // Invoke handler with dependency injection
            $result = $this->invokeAction($handler, $vars, $request);

            // Normalize the result into a Response object
            return $this->makeResponse($result);

        } 
        // catch (\Throwable $e) {
        //     return new Response(
        //         $e->getMessage(),
        //         method_exists($e, 'getCode') && $e->getCode() > 0 ? $e->getCode() : 500
        //     );
        // }
        catch (\Throwable $exception) {
            // Return as Response with proper HTTP code
            $response = new Response(
                $exception->getMessage(),
                method_exists($exception, 'getCode') && $exception->getCode() > 0 ? $exception->getCode() : 500
            );
        }
        return $response;
    }

    /**
     * Resolve a handler into a callable.
     */
    protected function resolveHandler(mixed $handler): callable
    {
        // Controller array [Controller::class, 'method']
        if (is_array($handler)) {
            [$controller, $method] = $handler;
            $instance = new $controller();
            return [$instance, $method];
        }

        // Invokable controller class string
        if (is_string($handler) && class_exists($handler)) {
            $instance = new $handler();
            if (is_callable($instance)) {
                return $instance;
            }
            throw new \RuntimeException("Controller {$handler} is not invokable.");
        }

        throw new \RuntimeException('Invalid route handler type.');
    }

    /**
     * Dynamically invoke a callable with dependency injection.
     */
    protected function invokeAction(callable|object $callable, array $vars, Request $request): mixed
    {
        $parameters = [];

        // Use Reflection to inspect the callable
        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_object($callable) && !($callable instanceof \Closure)) {
            $reflection = new ReflectionMethod($callable, '__invoke');
        } else {
            $reflection = new ReflectionFunction($callable);
        }

        // Map route parameters and Request object
        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();
            
            // Inject Request
            if ($type instanceof ReflectionNamedType && $type->getName() === Request::class) {
                $parameters[] = $request;
                continue;
            }

            // Inject route variables by name
            if (array_key_exists($param->getName(), $vars)) {
                $parameters[] = $vars[$param->getName()];
                continue;
            }

            // Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $parameters[] = $param->getDefaultValue();
                continue;
            }

            $parameters[] = null;
        }

        // Invoke the callable
        if ($reflection instanceof ReflectionMethod) {
            $obj = is_array($callable) ? $callable[0] : $callable;
            return $reflection->invokeArgs($obj, $parameters);
        }

        return $reflection->invokeArgs($parameters);
    }

    /**
     * Convert any result into a Response object.
     */
    protected function makeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return new Response($result, 200);
        }

        if (is_array($result)) {
            return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
        }

        return new Response('<h1>Empty Response</h1>', 200);
    }
}
