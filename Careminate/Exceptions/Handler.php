<?php declare(strict_types=1);

namespace Careminate\Exceptions;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Throwable;

/**
 * Global exception handler for the Careminate framework.
 */
class Handler
{
    protected bool $debug;

    public function __construct()
    {
        $this->debug = getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1';
    }

    public function render(?Request $request, Throwable $e): Response
    {
        $acceptsJson = $this->wantsJson($request);

        // Auth exceptions (401)
        if ($e instanceof AuthException) {
            $status = 401;
            $message = $this->debug ? $e->getMessage() : 'Unauthorized';

            if ($acceptsJson) {
                $payload = [
                    'error'   => 'Unauthorized',
                    'message' => $message,
                    'status'  => $status,
                ];
                return new Response(json_encode($payload, JSON_PRETTY_PRINT), $status, ['Content-Type' => 'application/json']);
            }

            $html = "<h1>401 Unauthorized</h1><p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
            return new Response($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // Generic exceptions
        $status = $this->determineStatusCode($e);

        if ($acceptsJson) {
            $payload = $this->formatJsonError($e, $status);
            return new Response(json_encode($payload, JSON_PRETTY_PRINT), $status, ['Content-Type' => 'application/json']);
        }

        $html = $this->formatHtmlError($e, $status);
        return new Response($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    protected function determineStatusCode(Throwable $e): int
    {
        return match (true) {
            $e instanceof \InvalidArgumentException => 400,
            $e instanceof \RuntimeException         => 500,
            default                                 => 500,
        };
    }

    protected function formatJsonError(Throwable $e, int $status): array
    {
        if ($this->debug) {
            return [
                'error'   => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTrace(),
                'status'  => $status,
            ];
        }

        return [
            'error'   => 'Server Error',
            'message' => 'Something went wrong. Please try again later.',
            'status'  => $status,
        ];
    }

    protected function formatHtmlError(Throwable $e, int $status): string
    {
        if ($this->debug) {
            return sprintf(
                "<h1>Error %d</h1>
                 <p><strong>Exception:</strong> %s</p>
                 <p><strong>Message:</strong> %s</p>
                 <p><strong>File:</strong> %s</p>
                 <p><strong>Line:</strong> %d</p>
                 <pre>%s</pre>",
                $status,
                htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8'),
                $e->getLine(),
                htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8')
            );
        }

        return sprintf(
            "<h1>Error %d</h1><p>Something went wrong. Please try again later.</p>",
            $status
        );
    }

    protected function wantsJson(?Request $request): bool
    {
        if (!$request) return false;
        $accept = $request->header('Accept') ?? '';
        return str_contains($accept, 'application/json') || $request->isAjax();
    }
}
