<?php declare(strict_types=1);
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
        $this->registerWebRoutes();
    }

    public function handle(Request $request): Response
    {
        return $this->router->dispatch($request);
    }

    protected function registerWebRoutes(): void
    {
        // require base_path('routes/web.php');
        require route_path('web.php');
    }

     protected function registerApiRoutes(): void
    {
        // require base_path('routes/app.php');
        require route_path('app.php');
    }

    public function terminate(Request $request, Response $response): void
    {
        // Perform any cleanup
    }
}

