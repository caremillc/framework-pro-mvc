<?php declare(strict_types=1);
namespace Careminate\EventDispatcher;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

class ResponseEvent extends Event
{
    public function __construct(private Request $request,private Response $response){}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}