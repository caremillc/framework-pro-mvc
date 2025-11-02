<?php declare(strict_types=1);

namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use App\Http\Middlewares\SuccessMiddleware;

class RequestHandler implements RequestHandlerInterface
{
    private array $middleware = [];

    public function __construct(private ContainerInterface $container)
    {
        $this->loadMiddlewares();
    }

    private function loadMiddlewares(): void
    {
        // Start with your built-in middlewares
        $middlewares = [
            StartSession::class,
            ExtractRouteInfo::class,
            VerifyCsrfToken::class, // only this code,
            RouterDispatch::class
        ];

        //add user defined middleware from app folder
        // Path to user middleware directory
        $middlewarePath = BASE_PATH . '/app/Http/Middlewares';

        if (is_dir($middlewarePath)) {
            foreach (new \DirectoryIterator($middlewarePath) as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    $fqcn = 'App\\Http\\Middlewares\\' . $className;

                    // Only add if class exists and not already in list
                    if (class_exists($fqcn) && !in_array($fqcn, $middlewares, true)) {
                        $middlewares[] = $fqcn;
                    }
                }
            }
        }

        $this->middleware = $middlewares;
    }

    public function handle(Request $request): Response
    {
        if (empty($this->middleware)) {
            return new Response("It's totally borked, mate. Contact support", 500);
        }

        // Get the next middleware class
        $middlewareClass = array_shift($this->middleware);

         $middleware = $this->container->get($middlewareClass);

        // Create an instance and call process()
        // $response = (new $middlewareClass())->process($request, $this);
          $response = $middleware->process($request, $this);

        return $response;
    }
     public function injectMiddleware(array $middleware): void
    { 
        //  dd($this->middleware);
        array_splice($this->middleware, 0, 0, $middleware);
    }
}
