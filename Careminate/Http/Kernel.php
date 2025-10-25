<?php declare(strict_types=1);

namespace Careminate\Http;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

class Kernel
{
    public function handle(Request $request): Response
    {
        $content = '<h1>Hello World from Kernel</h1>';
        $content .= '<p>Request Path: ' . htmlspecialchars($request->getPathInfo(), ENT_QUOTES, 'UTF-8') . '</p>';
        $content .= '<p>Request Method: ' . htmlspecialchars($request->getMethod(), ENT_QUOTES, 'UTF-8') . '</p>';
        $content .= '<p>User Agent: ' . htmlspecialchars($request->userAgent(), ENT_QUOTES, 'UTF-8') . '</p>';

        return new Response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
