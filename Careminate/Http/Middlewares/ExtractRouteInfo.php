<?php 
namespace Careminate\Http\Middlewares;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Exceptions\HttpException;
use function FastRoute\simpleDispatcher;
use Careminate\Exceptions\HttpRequestMethodException;
use Careminate\Http\Middlewares\RequestHandlerInterface;

class ExtractRouteInfo implements MiddlewareInterface
{
    public function __construct(private array $routes){}

    public function process(Request $request, RequestHandlerInterface $requestHandler): Response
    {
        // Create a dispatcher
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {

            foreach ($this->routes as $route) {
                $routeCollector->addRoute(...$route);
            }
        });

        // Dispatch a URI, to obtain the route info
        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getPathInfo()
        );

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                // Set $request->routeHandler
                $request->setRouteHandler($routeInfo[1]);
                // Set $request->routeHandlerArgs
                $request->setRouteHandlerArgs($routeInfo[2]);
                // Inject route middleware on handler
                if (is_array($routeInfo[1]) && isset($routeInfo[1][2])) {
                    $requestHandler->injectMiddleware($routeInfo[1][2]);
                }
                break;
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

        return $requestHandler->handle($request);
    }

    
}
