<?php 
namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

class MiddlewarePipeline
{
    /**
     * @param array $middleware Fully qualified class names
     */
    public static function run(Request $request, array $middleware, \Closure $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            function ($next, $middlewareClass) {
                return function ($request) use ($next, $middlewareClass) {
                    if (!class_exists($middlewareClass)) {
                        throw new \RuntimeException("Middleware class [$middlewareClass] does not exist.");
                    }

                    $middleware = new $middlewareClass();

                    if (!method_exists($middleware, 'handle')) {
                        throw new \RuntimeException("Middleware [$middlewareClass] must have a handle() method.");
                    }

                    return $middleware->handle($request, $next);
                };
            },
            $destination
        );

        return $pipeline($request);
    }
}
