<?php declare (strict_types = 1);
namespace Careminate\Http\Requests;

use Careminate\Support\Arr;
use Careminate\Http\Requests\Contracts\RequestInterface;

class Request implements RequestInterface
{

    protected string $method;
    protected string $uri;
    protected array $headers;
    protected array $queryParams;
    protected array $postParams;
    protected array $cookies;
    protected array $files;
    protected array $server;
    protected array $inputParams;
    protected string $rawInput;

    // Alias properties for backward compatibility
    protected array $getParams;
    protected array $params;

    private const METHODS_WITH_BODY = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const SPOOFABLE_METHODS = ['PUT', 'PATCH', 'DELETE'];

    public function __construct(
        array $queryParams = [],
        array $postParams = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        array $inputParams = [],
        string $rawInput = ''
    ) {
        $this->method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $server['REQUEST_URI'] ?? '/';
        $this->headers = $this->parseHeaders($server);
        $this->queryParams = $queryParams;
        $this->postParams = $postParams;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->inputParams = $inputParams;
        $this->rawInput = $rawInput;

        // Set aliases for backward compatibility
        $this->getParams = $this->queryParams;
        $this->params = array_merge($this->queryParams, $this->postParams, $this->inputParams);
    }


     public static function createFromGlobals(): static
    {
        $rawInput = file_get_contents('php://input');
        $inputParams = [];
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($rawInput !== '') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $inputParams = json_decode($rawInput, true) ?? [];
            } elseif (!in_array($requestMethod, ['GET', 'POST'], true)) {
                parse_str($rawInput, $inputParams);
            }
        }

        return new static(
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES,
            $_SERVER,
            $inputParams,
            $rawInput
        );
    }

    public function getMethod(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            $spoofedMethod = strtoupper(
                $this->postParams['_method'] ??
                $this->header('X-HTTP-Method-Override') ?? ''
            );

            if (in_array($spoofedMethod, self::SPOOFABLE_METHODS, true)) {
                return $spoofedMethod;
            }
        }

        return $method;
    }

   public function getUri(): string
    {
        return $this->uri;
    }

    public function getPathInfo(): string
    {
        return rtrim(parse_url($this->server['REQUEST_URI'] ?? '', PHP_URL_PATH), '/') ?: '/';
    }

    public function header(string $name): ?string
    {
        $name = strtoupper(str_replace('-', '_', $name));
        $serverKey = match ($name) {
            'CONTENT_TYPE', 'CONTENT_LENGTH' => $name,
            default => 'HTTP_' . $name
        };

        return $this->server[$serverKey] ?? null;
    }

    public function headers(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        return sprintf('%s://%s%s',
            $scheme,
            $this->server['HTTP_HOST'] ?? '',
            $this->server['REQUEST_URI'] ?? ''
        );
    }

     public function get(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $this->postParams[$key] ?? $this->inputParams[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->queryParams[$key])
            || isset($this->postParams[$key])
            || isset($this->inputParams[$key]);
    }

     public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    protected function parseHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }
        return $headers;
    }

    public function getHeader(string $name): ?string
    {
        $name = str_replace('_', '-', strtolower($name));
        foreach ($this->headers as $header => $value) {
            if (strtolower($header) === $name) {
                return $value;
            }
        }
        return null;
    }

    public function prefersContentType(string $contentType): bool
    {
        $acceptHeader = $this->getHeader('Accept') ?? '';
        $acceptParts  = explode(',', $acceptHeader);

        foreach ($acceptParts as $part) {
            $type = trim(explode(';', $part)[0]);
            if ($type === $contentType || $type === '*/*') {
                return true;
            }
        }

        return false;
    }

    public function acceptsHtml(): bool
    {
        return $this->prefersContentType('text/html') ||
        $this->prefersContentType('application/xhtml+xml');
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]['tmp_name']) && is_uploaded_file($this->files[$key]['tmp_name']);
    }

    public function allFiles(): array
    {
        return $this->files;
    }

    public function all(): array
    {
        return array_merge($this->getParams, $this->postParams, $this->inputParams);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $this->inputParams[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

     public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function getRawInput(): string
    {
        return $this->rawInput;
    }

    public function isJson(): bool
    {
        return (bool)preg_match('~[/+]json\b~i', $this->header('Content-Type') ?? '');
    }

    public function wantsJson(): bool
    {
        return (bool)preg_match('~[/+]json\b~i', $this->header('Accept') ?? '');
    }

    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function ip(): string
    {
        return $this->server['HTTP_CLIENT_IP']
            ?? $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['REMOTE_ADDR']
            ?? '';
    }

    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    public function only(array|string $keys): array
    {
        return Arr::only($this->all(), is_string($keys) ? func_get_args() : $keys);
    }

    public function except(array|string $keys): array
    {
        return Arr::except($this->all(), is_string($keys) ? func_get_args() : $keys);
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->getMethod();
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }
}

