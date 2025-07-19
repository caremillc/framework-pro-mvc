<?php declare (strict_types = 1);
namespace Careminate\Http;

use App\Http\HttpKernel;
use Careminate\Routing\Router;
use Careminate\Routing\Segment;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Routing\Contracts\RouterInterface;
use Careminate\Container\Contracts\ContainerInterface;

class Kernel
{
    public function __construct(protected RouterInterface $router, private ContainerInterface $container)
    {
        $this->router = new Router();

        // var_dump(Segment::get(0));

        $firstSegment = Segment::get(0); // safer
        if ($firstSegment === 'api') {
            $this->registerApiRoutes();
        } else {
            $this->registerWebRoutes();
        }
    }

    public function handle(Request $request): Response
    {
        $uri       = $request->getPathInfo();    // or $request->getPath()
        $method    = $request->getMethod();  // e.g., GET, POST
        return Router::dispatch($uri, $method, $this->container);
    }

    protected function registerWebRoutes(): void
    {
        foreach (HttpKernel::$globalWeb as $global) {
            new $global();
        }
        require route_path('web.php');
    }

    protected function registerApiRoutes(): void
    {
        foreach (HttpKernel::$globalApi as $global) {
            new $global();
        }
        require route_path('api.php');
    }

    public function terminate(Request $request, Response $response): void
    {
        // Perform any cleanup
    }
}
