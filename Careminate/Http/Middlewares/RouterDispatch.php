<?php  declare (strict_types = 1);
namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use Careminate\Routing\RouterInterface;
use Careminate\Exceptions\HttpException;

class RouterDispatch implements MiddlewareInterface
{
    public function __construct(
        private RouterInterface $router,
        private ContainerInterface $container
    )
    {
    }

    public function process(Request $request, RequestHandlerInterface $requestHandler): Response
    {
         [$routeHandler, $vars] = $this->router->dispatch($request, $this->container); //$this->container

            // Validate that the routeHandler is actually callable
            if (! is_callable($routeHandler)) {
                throw new HttpException('Route handler is not callable', 500);
            }

            $response = call_user_func_array($routeHandler, $vars);
            // Ensure the response is actually a Response object
            if (! $response instanceof Response) {
                return new Response((string) $response, 200);
            }

        return $response;
    }
}