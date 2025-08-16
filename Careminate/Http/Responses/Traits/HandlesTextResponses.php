<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Traits;

use Careminate\Http\Responses\Response;

trait HandlesTextResponses
{
    public static function html(string $html, int $status = 200, array $headers = []): Response
    {
        return new Response($html, $status, array_merge([
            'Content-Type' => 'text/html; charset=' . Response::DEFAULT_CHARSET,
        ], $headers));
    }

    public static function text(string $text, int $status = 200, array $headers = []): Response
    {
        return new Response($text, $status, array_merge([
            'Content-Type' => 'text/plain; charset=' . Response::DEFAULT_CHARSET,
        ], $headers));
    }

    public function withText(string $text): static
    {
        $this->content = $text;
        $this->setHeader('Content-Type', 'text/plain; charset=' . Response::DEFAULT_CHARSET);
        return $this;
    }

    public static function notFound(string $message = 'Not Found'): Response
    {
        return static::text($message, 404);
    }

    public static function badRequest(string $message = 'Bad Request'): Response
    {
        return static::text($message, 400);
    }

    public static function unauthorized(string $message = 'Unauthorized'): Response
    {
        return static::text($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): Response
    {
        return static::text($message, 403);
    }

    public static function serverError(string $message = 'Internal Server Error'): Response
    {
        return static::text($message, 500);
    }
}
