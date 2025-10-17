<?php declare(strict_types=1);
namespace Careminate\Http\Middleware;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

abstract class BaseMiddleware
{
    abstract public function handle(Request $request): ?Response;
}