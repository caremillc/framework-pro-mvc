<?php declare(strict_types=1);

namespace Careminate\Http;

use ReflectionMethod;
use ReflectionFunction;
use ReflectionNamedType;
use Careminate\Routing\Router;
use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use Careminate\Exceptions\HttpException;
use Careminate\Routing\Contracts\RouterInterface;

class Kernel
{ 
    private string $appEnv;
    private string $appKey;
    private string $appVersion;

    public function __construct(
        private RouterInterface $router,
        private ContainerInterface $container
    ){
        // Check .env file and configuration values
        if (!file_exists('.env') || !is_readable('.env')) {
            throw new \RuntimeException('.env file is missing or not readable.');
        }

        $this->appEnv = $this->container->get('APP_ENV');
        $this->appKey = $this->container->get('APP_KEY');
        $this->appVersion = $this->container->get('APP_VERSION');

        if (empty($this->appKey) || empty($this->appEnv) || empty($this->appVersion)) {
            throw new \RuntimeException('One or more required environment variables are missing.');
        }
    }
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
       catch (HttpException $exception) {
            $response = $this->createExceptionResponse($exception);
        }
        return $response;
    }

     private function createExceptionResponse(\Exception $exception): Response
	{
		// Check if the environment is development or local testing
		if (in_array($this->appEnv, ['dev', 'local', 'test'])) {
			// In development or local testing, rethrow the exception for detailed debugging
			throw $exception;
		}

		// Production environment handling
		if ($exception instanceof HttpException) {
			// Return a response with the HTTP status and message for HTTP exceptions
			return new Response($exception->getMessage(), $exception->getStatusCode());
		}

		// For all other exceptions, return a generic server error message
		return new Response('Server error', Response::HTTP_INTERNAL_SERVER_ERROR);
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
