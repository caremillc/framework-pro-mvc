<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Session\SessionInterface;
use Careminate\Http\Middlewares\RequestHandlerInterface;

class StartSession implements MiddlewareInterface
{
    public function __construct(
        private SessionInterface $session,
        private string $apiPrefix = '/api/'
    ){}

    public function process(Request $request, RequestHandlerInterface $requestHandler): Response
    { 
        // dd('start');
        if (!str_starts_with($request->getPathInfo(), $this->apiPrefix)) {
            $this->session->start();

            $request->setSession($this->session);
        }

        return $requestHandler->handle($request);
    }
}