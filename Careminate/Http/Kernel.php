<?php declare(strict_types=1);

namespace Careminate\Http;

use Careminate\Routing\Router;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\View\ViewEngines\ViewEngine;
use Careminate\Providers\ViewServiceProvider;

class Kernel
{
    protected Router $router;
    protected ViewServiceProvider $viewServiceProvider;
    protected ViewEngine $view;

    // public function __construct()
    // {
    //     $this->router = new Router;
    //     $this->viewServiceProvider = new ViewServiceProvider;
    //     $this->viewServiceProvider->register();
    //      // Initialize the ViewManager
    //     $this->view = new ViewManager(
    //         BASE_PATH . '/resources/views',
    //         BASE_PATH . '/storage/cache/views'
    //     );
    //     $this->loadRoutes();
    // }

   public function __construct()
{
    $this->router = new Router;
    $this->viewServiceProvider = new ViewServiceProvider;
    $this->viewServiceProvider->register();

    // Use the engine registered by the provider
    $this->view = $this->viewServiceProvider->engine();

    $this->loadRoutes();
}
    protected function loadRoutes(): void
    {
        $routesFile = BASE_PATH . '/routes/web.php';
        if (file_exists($routesFile)) {
            $router = $this->router; // make available to the routes file
            require $routesFile;
        }
    }

    
    public function handle(Request $request): Response
    {
        return $this->router->dispatch($request);
    }

    
    public function router(): \Careminate\Routing\Router
    {
        return $this->router;
    }
    public function terminate(Response $response): void
    {
        $response->send();
    }
    public function view(): ViewEngine
    {
        return $this->view;
    }
    public function viewEngine()
    {
        return $this->viewServiceProvider->engine();
    }
}
