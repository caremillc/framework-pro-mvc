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
    private string $appEnv;
    private string $appKey;
    private string $appVersion;

    
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

         // Check .env file and configuration values
        if (!file_exists('.env') || !is_readable('.env')) {
            throw new \RuntimeException('.env file is missing or not readable.');
        }

        $this->appEnv = $this->container->get('APP_ENV');
        $this->appKey = $this->container->get('APP_KEY');
        $this->appVersion = $this->container->get('APP_VERSION');

        if (empty($this->appKey) || empty($this->appEnv) || empty($this->appVersion)) {
            throw new \RuntimeException('One or more required environment variables are missing.');
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
