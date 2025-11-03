<?php declare (strict_types = 1);

namespace Careminate\Http\Requests;

use Careminate\Session\SessionInterface;
use Careminate\Support\Arr;

/**
 * ================================
 * HTTP Request - Production Ready with Validation
 * ================================
 */
class Request
{
    private SessionInterface $session;
    private mixed $routeHandler;
    private array $routeHandlerArgs;
    /**
     * HTTP methods that can contain request body data
     */
    private const METHODS_WITH_BODY = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Valid HTTP methods for spoofing
     */
    private const SPOOFABLE_METHODS = ['PUT', 'PATCH', 'DELETE'];

    private array $normalizedHeaders = [];
    private ?array $cachedAll        = null;
    private array $oldInput          = [];
    private array $errors            = [];
    protected $uri;
    protected $baseUrl;

    /**
     * Request constructor
     */
    public function __construct(
        private readonly array $getParams = [],
        private readonly array $postParams = [],
        private readonly ?string $body = null,
        private readonly array $cookies = [],
        private readonly array $files = [],
        private readonly array $server = [],
        public readonly array $inputParams = [],
        public readonly string $rawInput = '',

    ) {
        $this->normalizedHeaders = $this->normalizeHeaders($server);
        $this->cacheOldInput();
        $this->uri     = $_SERVER['REQUEST_URI'] ?? '/';
        $this->baseUrl = $this->resolveBaseUrl();
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

    // public function headers(): array
    // {
    //     $headers = [];
    //     foreach ($this->server as $key => $value) {
    //         if (str_starts_with($key, 'HTTP_')) {
    //             $headers[substr($key, 5)] = $value;
    //         } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
    //             $headers[$key] = $value;
    //         }
    //     }
    //     return $headers;
    // }

    public function header(string $name): ?string
    {
        return $this->normalizedHeaders[$name] ?? null;
    }
//   public function header(string $name): ?string
//     {
//         $name = strtoupper(str_replace('-', '_', $name));
//         $serverKey = match ($name) {
//             'CONTENT_TYPE', 'CONTENT_LENGTH' => $name,
//             default => 'HTTP_' . $name
//         };

//         return $this->server[$serverKey] ?? null;
//     }

    public function getHeaders(): array
    {
        return $this->headers();
    }

    /**
     * Get all headers
     */
    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function getMethod(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') {
            $spoofedMethod = strtoupper($this->postParams['_method'] ?? $this->header('X-HTTP-Method-Override') ?? '');
            if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoofedMethod;
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

//  public function all(): array
//     {
//         return array_merge($this->getParams, $this->postParams, $this->inputParams);
//     }

    public function input(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    //  public function input(string $key, mixed $default = null): mixed
    // {
    //     return $this->postParams[$key] ?? $this->inputParams[$key] ?? $default;
    // }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }
    //  public function get(string $key, mixed $default = null): mixed
    // {
    //     return $this->getParams[$key] ?? $this->postParams[$key] ?? $this->inputParams[$key] ?? $default;
    // }
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

    //  public function has(string $key): bool
    // {
    //     return isset($this->getParams[$key])
    //         || isset($this->postParams[$key])
    //         || isset($this->inputParams[$key]);
    // }

    public function query(string $key, mixed $default = null): mixed
    {
        return data_get($this->getParams, $key, $default);
    }

    // 	public function query(string $key, mixed $default = null): mixed
    // {
    //     return $this->getParams[$key] ?? $default;
    // }

    public function post(string $key, mixed $default = null): mixed
    {
        return data_get($this->postParams, $key, $default);
    }

    //   public function post(string $key, mixed $default = null): mixed
    // {
    //     return $this->postParams[$key] ?? $default;
    // }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return data_get($this->cookies, $key, $default);
    }

//   public function cookie(string $key, mixed $default = null): mixed
//     {
//         return $this->cookies[$key] ?? $default;
//     }

    // public function file(string $key): ?array
    // {
    //     return $this->files[$key] ?? null;
    // }

    // public function hasFile(string $key): bool
    // {
    //     $file = $this->file($key);
    //     return isset($file['tmp_name'], $file['name']) && is_uploaded_file($file['tmp_name']);
    // }

    // public function hasFile(string $key): bool
    // {
    //     return isset($this->files[$key]['tmp_name']) && is_uploaded_file($this->files[$key]['tmp_name']);
    // }

    public function allFiles(): array
    {
        return $this->files;
    }

    public function json(): array
    {
        $data = json_decode($this->rawInput, true);
        return is_array($data) ? $data : [];
    }

    // public function isJson(): bool
    // {
    //     return str_contains((string) $this->header('Content-Type'), 'json');
    // }

    // public function wantsJson(): bool
    // {
    //     return str_contains((string) $this->header('Accept'), 'json');
    // }

    public function isJson(): bool
    {
        return (bool) preg_match('~[/+]json\b~i', $this->header('Content-Type') ?? '');
    }

    public function wantsJson(): bool
    {
        return (bool) preg_match('~[/+]json\b~i', $this->header('Accept') ?? '');
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

            // For file fields, use the file data instead of input
            if ($this->hasFile($field)) {
                $value = $this->file($field);
            }

            foreach ($rulesArr as $rule) {
                [$ruleName, $param] = $this->parseRule($rule);
                match ($ruleName) {
                    'required' => $this->validateRequired($field, $value, $errors),
                    'string'   => $this->validateString($field, $value, $errors),
                    'numeric'  => $this->validateNumeric($field, $value, $errors),
                    'email'    => $this->validateEmail($field, $value, $errors),
                    'min'      => $this->validateMin($field, $value, $param, $errors),
                    'max'      => $this->validateMax($field, $value, $param, $errors),
                    'image'    => $this->validateImage($field, $value, $errors),
                    'mimes'    => $this->validateMimes($field, $value, $param, $errors),
                    'max_file' => $this->validateMaxFile($field, $value, $param, $errors),
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

/**
 * Parse rule to get rule name and parameter
 */
    private function parseRule(string $rule): array
    {
        $parts    = explode(':', $rule);
        $ruleName = $parts[0];
        $param    = $parts[1] ?? null;

        return [$ruleName, $param];
    }

/**
 * Validate required field
 */
    private function validateRequired(string $field, $value, array &$errors): void
    {
        if (is_array($value)) {
            // For file uploads
            if (! isset($value['error']) || $value['error'] === UPLOAD_ERR_NO_FILE) {
                $errors[$field][] = "$field is required";
            }
        } else {
            // For regular fields
            if ($value === null || $value === '') {
                $errors[$field][] = "$field is required";
            }
        }
    }

/**
 * Validate string field
 */
    private function validateString(string $field, $value, array &$errors): void
    {
        if ($value !== null && $value !== '' && ! is_string($value)) {
            $errors[$field][] = "$field must be a string";
        }
    }

/**
 * Validate numeric field
 */
    private function validateNumeric(string $field, $value, array &$errors): void
    {
        if ($value !== null && $value !== '' && ! is_numeric($value)) {
            $errors[$field][] = "$field must be numeric";
        }
    }

/**
 * Validate email field
 */
    private function validateEmail(string $field, $value, array &$errors): void
    {
        if ($value !== null && $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field][] = "$field must be a valid email";
        }
    }

/**
 * Validate minimum value/length
 */
    private function validateMin(string $field, $value, $param, array &$errors): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_numeric($value)) {
            if ($value < (int) $param) {
                $errors[$field][] = "$field must be at least $param";
            }
        } elseif (is_string($value)) {
            if (strlen($value) < (int) $param) {
                $errors[$field][] = "$field must be at least $param characters";
            }
        }
    }

/**
 * Validate maximum value/length
 */
    private function validateMax(string $field, $value, $param, array &$errors): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_numeric($value)) {
            if ($value > (int) $param) {
                $errors[$field][] = "$field must be less than or equal to $param";
            }
        } elseif (is_string($value)) {
            if (strlen($value) > (int) $param) {
                $errors[$field][] = "$field must be less than $param characters";
            }
        }
    }

/**
 * Validate image file
 */
    private function validateImage(string $field, $value, array &$errors): void
    {
        if (! is_array($value) || ! isset($value['error'])) {
            $errors[$field][] = "$field must be an image file";
            return;
        }

        // Check if file was uploaded
        if ($value['error'] === UPLOAD_ERR_NO_FILE) {
            return; // Not required, so skip if no file
        }

        if ($value['error'] !== UPLOAD_ERR_OK) {
            $errors[$field][] = "$field upload failed";
            return;
        }

        // Check if it's actually an image
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType     = finfo_file($finfo, $value['tmp_name']);
        finfo_close($finfo);

        if (! in_array($mimeType, $allowedTypes)) {
            $errors[$field][] = "$field must be a valid image (JPEG, PNG, GIF, WebP, SVG)";
        }
    }

/**
 * Validate file MIME types
 */
    private function validateMimes(string $field, $value, $param, array &$errors): void
    {
        if (! is_array($value) || ! isset($value['error']) || $value['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }

        if ($value['error'] !== UPLOAD_ERR_OK) {
            return; // Error handled by required rule
        }

        $allowedTypes = explode(',', $param);
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType     = finfo_file($finfo, $value['tmp_name']);
        finfo_close($finfo);

        $extension = pathinfo($value['name'], PATHINFO_EXTENSION);

        // Check both MIME type and extension for safety
        $isValid = in_array($mimeType, $this->mapExtensionsToMimeTypes($allowedTypes)) ||
        in_array(strtolower($extension), array_map('strtolower', $allowedTypes));

        if (! $isValid) {
            $errors[$field][] = "$field must be of type: " . str_replace(',', ', ', $param);
        }
    }

/**
 * Validate maximum file size (in KB)
 */
    private function validateMaxFile(string $field, $value, $param, array &$errors): void
    {
        if (! is_array($value) || ! isset($value['error']) || $value['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }

        if ($value['error'] !== UPLOAD_ERR_OK) {
            return; // Error handled by required rule
        }

        $maxSize = (int) $param * 1024; // Convert KB to bytes
        if ($value['size'] > $maxSize) {
            $errors[$field][] = "$field must be less than $param KB";
        }
    }

/**
 * Map file extensions to MIME types
 */
    private function mapExtensionsToMimeTypes(array $extensions): array
    {
        $mimeMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt'  => 'text/plain',
        ];

        $mimes = [];
        foreach ($extensions as $ext) {
            $ext = strtolower(trim($ext));
            if (isset($mimeMap[$ext])) {
                $mimes[] = $mimeMap[$ext];
            }
        }

        return array_unique($mimes);
    }

/**
 * Check if request has file
 */
    public function hasFile(string $field): bool
    {
        return isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE;
    }

/**
 * Get uploaded file data
 */
    public function file(string $field): ?array
    {
        return $_FILES[$field] ?? null;
    }

// end validation

    // private function parseRule(string $rule): array
    // {
    //     if (str_contains($rule, ':')) {
    //         return explode(':', $rule, 2);
    //     }
    //     return [$rule, null];
    // }

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

    //    public function ip(): string
    // {
    //     return $this->server['HTTP_CLIENT_IP'] ?? $this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? '';
    // }
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

    //    public function userAgent(): ?string
    // {
    //     return $this->header('User-Agent');
    // }

    public function raw(): string
    {
        return $this->rawInput;
    }

    /**
     * Get the current path info for the request
     */
    public function path(): string
    {
        // Remove query string
        $path = parse_url($this->uri, PHP_URL_PATH) ?: '/';

        // Remove base URL if present (for subdirectory installations)
        if ($this->baseUrl && strpos($path, $this->baseUrl) === 0) {
            $path = substr($path, strlen($this->baseUrl));
        }

        // Sanitize and return
        $path = trim($path, '/');

        return $path === '' ? '/' : $path;
    }

    /**
     * Get path segments as array
     */
    public function segments(): array
    {
        $path = $this->path();
        return $path === '/' ? [] : explode('/', trim($path, '/'));
    }

    /**
     * Get specific path segment
     */
    public function segment(int $index): ?string
    {
        $segments = $this->segments();
        return $segments[$index - 1] ?? null;
    }

    /**
     * Resolve base URL for applications in subdirectories
     */
    protected function resolveBaseUrl(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl    = dirname($scriptName);

        return $baseUrl === '/' ? '' : $baseUrl;
    }

    /**
     * Get full URI with query string
     */
    public function fullUri(): string
    {
        return $this->uri;
    }

    // public function fullUrl(): string
    // {
    //     $scheme = $this->isSecure() ? 'https' : 'http';
    //     return sprintf('%s://%s%s',
    //         $scheme,
    //         $this->server['HTTP_HOST'] ?? '',
    //         $this->server['REQUEST_URI'] ?? ''
    //     );
    // }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host   = $this->server['HTTP_HOST'] ?? ($this->server['SERVER_NAME'] ?? 'localhost');
        return sprintf('%s://%s%s', $scheme, $host, $this->server['REQUEST_URI'] ?? '');
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function getRawInput(): string
    {
        return $this->rawInput;
    }

    public function only(array | string $keys): array
    {
        return Arr::only($this->all(), is_string($keys) ? func_get_args() : $keys);
    }

    public function except(array | string $keys): array
    {
        return Arr::except($this->all(), is_string($keys) ? func_get_args() : $keys);
    }

    public function files(?string $key = null)
    {
        if ($key === null) {
            return $_FILES;
        }
        return $_FILES[$key] ?? null;
    }
    // start sessions
    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    public function hasSession(): bool
    {
        return $this->session !== null;
    }

    //end sessions

    public function getRouteHandler(): mixed
    {
        return $this->routeHandler;
    }

    public function setRouteHandler(mixed $routeHandler): void
    {
        $this->routeHandler = $routeHandler;
    }

    public function getRouteHandlerArgs(): array
    {
        return $this->routeHandlerArgs;
    }

    public function setRouteHandlerArgs(array $routeHandlerArgs): void
    {
        $this->routeHandlerArgs = $routeHandlerArgs;
    }
}
