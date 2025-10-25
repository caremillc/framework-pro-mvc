<?php declare(strict_types=1);

namespace Careminate\Http;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use Careminate\Routing\RouterInterface;
use Careminate\Exceptions\HttpException;

/**
 * HTTP Kernel
 * 
 * The Kernel class serves as the central point for handling incoming HTTP requests
 * and returning the appropriate responses by dispatching them through the router.
 */
class Kernel
{
    /**
     * Create a new Kernel instance
     * 
     * @param Router $router Router instance used for dispatching requests
     */
    // public function __construct(private Router $router) {}
     public function __construct(
        private RouterInterface $router,
        private ContainerInterface $container
    ){}

    /**
     * Handle the incoming HTTP request
     * 
     * Dispatches the request through the router, executes the appropriate handler,
     * and returns the resulting response. Catches any exceptions and returns them
     * as error responses.
     * 
     * @param Request $request The incoming HTTP request
     * @return Response The HTTP response
     */
    public function handle(Request $request): Response
    {
        try {

            [$routeHandler, $vars] = $this->router->dispatch($request, $this->container);  //$this->container


            $response = call_user_func_array($routeHandler, $vars);

        } catch (HttpException $exception) {
            $response = new Response($exception->getMessage(), $exception->getStatusCode());
        } catch (\Exception $exception) {
            $response = new Response($exception->getMessage(), 500);
        }

        return $response;
    }

}

