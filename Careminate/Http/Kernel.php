<?php declare(strict_types=1);
namespace Careminate\Http;

use App\Http\HttpKernel;
use Careminate\Routing\Router;
use Careminate\Routing\Segment;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

class Kernel 
{

    protected Router $router;

    public function __construct()
    {
        $this->router = new Router();
       
        var_dump(Segment::get(1));

         if (Segment::get(1) == 'api') {
             $this->registerApiRoutes();
        } else {
             $this->registerWebRoutes();
        }
    }

    public function handle(Request $request): Response
    {
        return $this->router->dispatch($request);
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

