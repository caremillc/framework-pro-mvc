<?php declare (strict_types = 1);

namespace Careminate\Http;

use Doctrine\DBAL\Connection;
use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use Careminate\Routing\RouterInterface;
use Careminate\Exceptions\HttpException;
use Careminate\EventDispatcher\ResponseEvent;
use Careminate\EventDispatcher\EventDispatcher;
use Careminate\Http\Middlewares\RequestHandlerInterface;

/**
 * HTTP Kernel
 *
 * The Kernel class serves as the central point for handling incoming HTTP requests
 * and returning the appropriate responses by dispatching them through the router.
 */
class Kernel
{
    private string $appEnv;
    private string $appKey;
    private string $appVersion;
    /**
     * Create a new Kernel instance
     *
     * @param Router $router Router instance used for dispatching requests
     */
    // public function __construct(private Router $router) {}
    public function __construct(
        // private RouterInterface $router,
        private ContainerInterface $container,
        private RequestHandlerInterface $requestHandler,
        private EventDispatcher $eventDispatcher  //step 1
    ) {
        // Check .env file and configuration values
        if (! file_exists('.env') || ! is_readable('.env')) {
            throw new \RuntimeException('.env file is missing or not readable.');
        }

        $this->appEnv     = $this->container->get('APP_ENV');
        $this->appKey     = $this->container->get('APP_KEY');
        $this->appVersion = $this->container->get('APP_VERSION');

        if (empty($this->appKey) || empty($this->appEnv) || empty($this->appVersion)) {
            throw new \RuntimeException('One or more required environment variables are missing.');
        }
    }
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

            //  dd($this->container->get(Connection::class));
            // passing the request into the middleware handler
             $response = $this->requestHandler->handle($request);
             
           
        } catch (HttpException $exception) {
            $response = $this->createExceptionResponse($exception);
        }

        $this->eventDispatcher->dispatch(new ResponseEvent($request, $response));  //step 2 
        return $response;
    }

    private function createExceptionResponse(\Exception $exception): Response
    {
        // Check if the environment is development or local testing
        if (in_array($this->appEnv, ['dev', 'local', 'test'])) {
            // In development or local testing, rethrow the exception for detailed debugging
            throw $exception;
        }

        // Production environment handling
        if ($exception instanceof HttpException) {
            // Return a response with the HTTP status and message for HTTP exceptions
            return new Response($exception->getMessage(), $exception->getStatusCode());
        }

        // For all other exceptions, return a generic server error message
        return new Response('Server error', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    //   public function terminate(Request $request, Response $response): void
    // {
    //     $request->getSession()?->clearFlash();
    // }

    public function terminate(Request $request, Response $response): void
	{
		if ($request->hasSession()) {
			$session = $request->getSession();
			// Perform termination tasks with session
			// $request->getSession()?->clearFlash();
			$session->clearFlash();
		}
	}

}
