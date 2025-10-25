<?php declare(strict_types=1);

namespace Careminate\Http\Responses;

class RedirectResponse extends Response
{
    protected bool $exitAfterRedirect = true;
    protected bool $preserveRelative;

    public function __construct(string $url, int $status = self::HTTP_FOUND)
    {
        $this->preserveRelative = $this->shouldPreserveRelative();

        // Adjust status for API
        if ($this->isApiRequest() && $status === self::HTTP_FOUND) {
            $status = self::HTTP_SEE_OTHER; // 303
        }

        $url = $this->normalizeUrl($url);
        parent::__construct('', $status, ['Location' => $url]);
    }

    public function setExitAfterRedirect(bool $exit): static
    {
        $this->exitAfterRedirect = $exit;
        return $this;
    }

    public function preserveRelative(bool $preserve = true): static
    {
        $this->preserveRelative = $preserve;

        $url = $this->getHeader('Location');
        if ($url !== null) {
            $this->setHeader('Location', $this->normalizeUrl($url));
        }

        return $this;
    }

    public function withUrl(string $url): static
    {
        $url = $this->normalizeUrl($url);
        $clone = clone $this;
        $clone->setHeader('Location', $url);
        return $clone;
    }

    public function send(): void
    {
        if ($this->areHeadersSent()) {
            return;
        }

        $url = $this->getHeader('Location');
        if (!$url) {
            throw new \RuntimeException('No location header set for redirect.');
        }

        // Handle API differently
        if ($this->isApiRequest()) {
            $this->setHeader('Content-Type', 'application/json; charset=utf-8');

            echo json_encode([
                'redirect' => $url,
                'status'   => $this->getStatus(),
            ], JSON_UNESCAPED_SLASHES);

            if ($this->exitAfterRedirect) {
                exit;
            }
            return;
        }

        // Normal browser redirect
        http_response_code($this->getStatus());
        header("Location: $url", true, $this->getStatus());

        if ($this->exitAfterRedirect) {
            exit;
        }
    }

    protected function areHeadersSent(): bool
    {
        return headers_sent();
    }

    protected function normalizeUrl(string $url): string
    {
        if ($this->preserveRelative) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $parts     = parse_url($url);
        $path      = $parts['path'] ?? '';
        $query     = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment  = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $basePath = rtrim(str_replace('\\', '/', dirname(parse_url($uri, PHP_URL_PATH) ?? '')), '/');

        if (str_starts_with($path, '/')) {
            return "$scheme://$host$path$query$fragment";
        }

        $fullPath = $basePath . '/' . ltrim($path, './');
        $normalizedPath = $this->resolvePath($fullPath);

        return "$scheme://$host$normalizedPath$query$fragment";
    }

    protected function resolvePath(string $path): string
    {
        $segments = explode('/', $path);
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $segment;
            }
        }

        return '/' . implode('/', $resolved);
    }

    protected function shouldPreserveRelative(): bool
    {
        $global = $this->getDefaultPreserveRelative();

        if ($this->isApiRequest()) {
            return true;
        }

        return $global;
    }

    protected function getDefaultPreserveRelative(): bool
    {
        if (function_exists('config')) {
            return (bool) config('http.redirects.preserve_relative', false);
        }

        if (function_exists('env')) {
            return filter_var(env('REDIRECT_PRESERVE_RELATIVE', false), FILTER_VALIDATE_BOOL);
        }

        return false;
    }

    protected function isApiRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'json')
            || strcasecmp($xrw, 'XMLHttpRequest') === 0;
    }
}
