<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares;

use Careminate\Session\Session;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Session\SessionInterface;

class Authenticate implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session){}

   public function process(Request $request, RequestHandlerInterface $requestHandler): Response
    {
         $this->session->start();
        // if (!$this->authenticated) {
        //     return new Response('Authentication failed', 401);
        // }
         if (!$this->session->has(Session::AUTH_KEY)) {
            $this->session->setFlash('error', 'Please sign in');
            return redirect('/login');
        }

        return $requestHandler->handle($request);
    }
}

