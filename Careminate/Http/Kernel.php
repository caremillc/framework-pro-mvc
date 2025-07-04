<?php 
namespace Careminate\Http;

use Careminate\Routing\Router;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

class Kernel 
{
    protected Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

   public function handle(Request $request): Response
    {
        return $this->router->dispatch($request->getPathInfo(), $request->getMethod());
    }

    protected function registerRoutes(): void
    {
        // require base_path('routes/web.php');
        require route_path('web.php');
    }

    public function terminate(Request $request, Response $response): void
    {
        // Perform any cleanup
    }
}
