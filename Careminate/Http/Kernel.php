<?php declare (strict_types = 1);
namespace Careminate\Http;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Routing\Router;
use Careminate\Routing\Segment;

class Kernel
{

    protected Router $router;

    public function __construct()
    {
        $this->router = new Router();
       
       // var_dump(Segment::get(1));

        $firstSegment = Segment::get(1);
        // dd($firstSegment);
        if ($firstSegment === 'api') {
            $this->registerApiRoutes();
        } else {
            $this->registerWebRoutes();
        }

    }

    public function handle(Request $request): Response
    {
        return $this->router->dispatch($request->getPathInfo(), $request->getMethod());
    }

    protected function registerWebRoutes(): void
    {
        //  foreach (HttpKernel::$globalWeb as $global) {
        //     new $global();
        // }
        require route_path('web.php');
    }

    protected function registerApiRoutes(): void
    {
        //  foreach (HttpKernel::$globalApi as $global) {
        //     new $global();
        // }
        require route_path('api.php');
    }

    public function terminate(Request $request, Response $response): void
    {
        // Perform any cleanup
    }
}
