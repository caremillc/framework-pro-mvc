<?php declare (strict_types = 1);

namespace Careminate\Http\Requests;

use Careminate\Support\Arr;

/**
 * ================================
 * HTTP Request - Production Ready with Validation
 * ================================
 */
class Request
{
    private const METHODS_WITH_BODY = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const SPOOFABLE_METHODS = ['PUT', 'PATCH', 'DELETE'];

    private array $normalizedHeaders = [];
    private ?array $cachedAll        = null;
    private array $oldInput          = [];
    private array $errors            = [];

    public function __construct(
        private readonly array $getParams = [],
        private readonly array $postParams = [],
        private readonly ?string $body = null,
        private readonly array $cookies = [],
        private readonly array $files = [],
        private readonly array $server = [],
        public readonly array $inputParams = [],
        public readonly string $rawInput = ''
    ) {
        $this->normalizedHeaders = $this->normalizeHeaders($server);
        $this->cacheOldInput();
    }

    /**
     * Create Request from global variables
     */

    public static function createFromGlobals(): static
    {
        $method      = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $rawInput    = file_get_contents('php://input') ?: '';
        $inputParams = [];

        if ($rawInput !== '') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'json')) {
                $inputParams = json_decode($rawInput, true) ?? [];
            } elseif (! in_array($method, ['GET', 'POST'], true)) {
                parse_str($rawInput, $inputParams);
            }
        }

        return new static(
            $_GET,
            $_POST,
            $rawInput,
            $_COOKIE,
            $_FILES,
            $_SERVER,
            $inputParams,
            $rawInput
        );
    }

    /**
     * ================================
     * Header & Server
     * ================================
     */

    private function normalizeHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }
        return $headers;
    }

    public function headers(): array
    {
        return $this->normalizedHeaders;
    }

    public function header(string $name): ?string
    {
        return $this->normalizedHeaders[$name] ?? null;
    }

    /**
     * Get all headers
     */
    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host   = $this->server['HTTP_HOST'] ?? ($this->server['SERVER_NAME'] ?? 'localhost');
        return sprintf('%s://%s%s', $scheme, $host, $this->server['REQUEST_URI'] ?? '');
    }

    public function getMethod(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') {
            $spoofed = strtoupper($this->postParams['_method'] ?? $this->header('X-HTTP-Method-Override') ?? '');
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoofed;
            }
        }
        return $method;
    }

    /**
     * Request URI / Path
     */
    public function getPathInfo(): string
    {
        return rtrim(parse_url($this->server['REQUEST_URI'] ?? '', PHP_URL_PATH), '/') ?: '/';
    }

    /**
     * Get HTTP method (with spoofing)
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->getMethod();
    }
    public function isPost(): bool
    {return $this->isMethod('POST');}
    public function isGet(): bool
    {return $this->isMethod('GET');}
    public function isPut(): bool
    {return $this->isMethod('PUT');}
    public function isPatch(): bool
    {return $this->isMethod('PATCH');}
    public function isDelete(): bool
    {return $this->isMethod('DELETE');}
    public function isHead(): bool
    {return $this->isMethod('HEAD');}
    public function isOptions(): bool
    {return $this->isMethod('OPTIONS');}

    /**
     * ================================
     * Input & Query
     * ================================
     */
    public function all(): array
    {
        return $this->cachedAll ??= array_merge($this->getParams, $this->postParams, $this->inputParams);
    }

    public function only(array | string ...$keys): array
    {
        return Arr::only($this->all(), $keys);
    }
    public function except(array | string ...$keys): array
    {
        return Arr::except($this->all(), $keys);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }
    /**
     * Whether the request is an AJAX (XMLHttpRequest) request.
     */
    public function isAjax(): bool
    {
        $xrw = $this->header('X-Requested-With');
        return $xrw !== null && strtolower($xrw) === 'xmlhttprequest';
    }

    /**
     * Get raw body content.
     */
    public function getContent(): ?string
    {
        return $this->body;
    }

    public function has(string $key): bool
    {
        return Arr::has($this->all(), $key);
    }
    public function query(string $key, mixed $default = null): mixed
    {
        return data_get($this->getParams, $key, $default);
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return data_get($this->postParams, $key, $default);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return data_get($this->cookies, $key, $default);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return isset($file['tmp_name'], $file['name']) && is_uploaded_file($file['tmp_name']);
    }

    public function allFiles(): array
    {
        return $this->files;
    }
    public function json(): array
    {
        $data = json_decode($this->rawInput, true);
        return is_array($data) ? $data : [];
    }

    public function isJson(): bool
    {
        return str_contains((string) $this->header('Content-Type'), 'json');
    }

    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('Accept'), 'json');
    }
    /**
     * ================================
     * Old Input
     * ================================
     */
    private function cacheOldInput(): void
    {
        $this->oldInput = $this->all();
    }

    public function old(string $key, mixed $default = null): mixed
    {
        return data_get($this->oldInput, $key, $default);
    }

    /**
     * ================================
     * Validation
     * ================================
     */
    public function validate(array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $rulesArr = explode('|', $ruleString);
            $value    = $this->input($field);

            foreach ($rulesArr as $rule) {
                [$ruleName, $param] = $this->parseRule($rule);
                match ($ruleName) {
                    'required' => $value === null || $value === '' ? $errors[$field][]        = "$field is required" : null,
                    'string'   => ! is_string($value) ? $errors[$field][]                        = "$field must be a string" : null,
                    'numeric'  => ! is_numeric($value) ? $errors[$field][]                      = "$field must be numeric" : null,
                    'email'    => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? $errors[$field][] = "$field must be a valid email" : null,
                    'min'      => is_numeric($value) && $value < (int) $param ? $errors[$field][]  = "$field must be >= $param" : null,
                    'max'      => is_numeric($value) && $value > (int) $param ? $errors[$field][]  = "$field must be <= $param" : null,
                    default    => null
                };
            }
        }

        $this->errors = $errors;

        if (! empty($errors)) {
            throw new \RuntimeException('Validation failed: ' . json_encode($errors));
        }

        return $this->all();
    }

    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            return explode(':', $rule, 2);
        }
        return [$rule, null];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Client IP
     */
    public function ip(): string
    {
        $ip = $this->server['HTTP_CLIENT_IP'] ?? $this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? '';
        return explode(',', $ip)[0] ?? '';
    }

    /**
     * User-Agent
     */

    /**
     * Modernized userAgent() method with fallback
     */
    public function userAgent(): string
    {
        return $this->header('User-Agent') ?? $this->server['HTTP_USER_AGENT'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? php_sapi_name() . '-cli');
    }
    public function raw(): string
    {
        return $this->rawInput;
    }

}
