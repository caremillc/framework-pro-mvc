<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

interface RequestHandlerInterface
{
    public function handle(Request $request): Response;
    public function injectMiddleware(array $middleware): void; // Add this line
}
