<?php declare(strict_types=1);

namespace Careminate\Middlewares\Contracts;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response;
}
