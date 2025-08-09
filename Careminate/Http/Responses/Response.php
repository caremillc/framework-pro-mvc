<?php declare (strict_types = 1);

namespace Careminate\Http\Responses;

use Careminate\Http\Responses\Contracts\Macroable;
use Careminate\Http\Responses\Contracts\ResponseInterface;
use Careminate\Http\Responses\Traits\HandlesFileResponses;
use Careminate\Http\Responses\Traits\HandlesJsonResponses;
use Careminate\Http\Responses\Traits\HandlesTextResponses;
use Careminate\Http\Responses\Traits\HandlesThrowableResponses;
use Careminate\Http\Responses\Traits\HandlesXmlResponses;
use Careminate\Http\Responses\Traits\HasMacros;
use Nyholm\Psr7\Factory\Psr17Factory;

class Response implements ResponseInterface
{
   
    use HandlesJsonResponses;
    use HandlesXmlResponses;
    use HandlesTextResponses;
    use HandlesFileResponses;
    use HandlesThrowableResponses;

    public const DEFAULT_CHARSET = 'UTF-8';

    // Change from instance to static property
    private static ?\Closure $jsonSerializer = null;

    private bool $disableStreaming = false;
    /**
     * Default JSON encode options
     */
    private const DEFAULT_JSON_OPTIONS = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * JSON options for encoding responses
     */
    private static int $jsonOptions = self::DEFAULT_JSON_OPTIONS;

    /**
     * Predefined HTTP status text mappings
     */
    public const HTTP_STATUS_TEXTS = [
        // Informational
        100 => 'Continue', 101                        => 'Switching Protocols', 102  => 'Processing', 103         => 'Early Hints',
        // Success
        200 => 'OK', 201                              => 'Created', 202              => 'Accepted', 203           => 'Non-Authoritative Information',
        204 => 'No Content', 205                      => 'Reset Content', 206        => 'Partial Content',
        207 => 'Multi-Status', 208                    => 'Already Reported', 226     => 'IM Used',
        // Redirection
        300 => 'Multiple Choices', 301                => 'Moved Permanently', 302    => 'Found', 303              => 'See Other',
        304 => 'Not Modified', 305                    => 'Use Proxy', 307            => 'Temporary Redirect', 308 => 'Permanent Redirect',
        // Client errors
        400 => 'Bad Request', 401                     => 'Unauthorized', 402         => 'Payment Required', 403   => 'Forbidden',
        404 => 'Not Found', 405                       => 'Method Not Allowed', 406   => 'Not Acceptable',
        407 => 'Proxy Authentication Required', 408   => 'Request Timeout', 409      => 'Conflict',
        410 => 'Gone', 411                            => 'Length Required', 412      => 'Precondition Failed',
        413 => 'Payload Too Large', 414               => 'URI Too Long', 415         => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable', 417           => 'Expectation Failed', 418   => 'I\'m a teapot',
        421 => 'Misdirected Request', 422             => 'Unprocessable Entity', 423 => 'Locked',
        424 => 'Failed Dependency', 425               => 'Too Early', 426            => 'Upgrade Required',
        428 => 'Precondition Required', 429           => 'Too Many Requests',
        431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
        // Server errors
        500 => 'Internal Server Error', 501           => 'Not Implemented', 502      => 'Bad Gateway',
        503 => 'Service Unavailable', 504             => 'Gateway Timeout', 505      => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates', 507         => 'Insufficient Storage', 508 => 'Loop Detected',
        510 => 'Not Extended', 511                    => 'Network Authentication Required',
    ];

    // Common HTTP status constants
    public const HTTP_OK                    = 200;
    public const HTTP_CREATED               = 201;
    public const HTTP_NO_CONTENT            = 204;
    public const HTTP_FOUND                 = 302;
    public const HTTP_BAD_REQUEST           = 400;
    public const HTTP_UNAUTHORIZED          = 401;
    public const HTTP_FORBIDDEN             = 403;
    public const HTTP_NOT_FOUND             = 404;
    public const HTTP_CONFLICT              = 409;
    public const HTTP_UNPROCESSABLE_ENTITY  = 422;
    public const HTTP_TOO_MANY_REQUESTS     = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_SERVICE_UNAVAILABLE   = 503;

    // Use max 80% of memory_limit
    private const MAX_MEMORY_PERCENT = 0.8;
    // Optimal chunk size for streaming
    private const CHUNK_SIZE = 8192;

    /**
     * The response content
     */
    private string $content;

    /**
     * The response status code
     */
    private int $status;

    /**
     * The response headers
     *
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * Whether headers have been sent already
     */
    private bool $headersSent = false;

    /**
     * Response constructor.
     */
    public function __construct(string $content = '', int $status = self::HTTP_OK, array $headers = [])
    {
        $this->content = $content;
        $this->status  = $status;
        $this->headers = $headers;
    }

     /**
      * send
      *
      * @return void
      */
     public function send(): void
    {
        $this->ensureNoOutputConflict();
        $this->sendHeaders();
        $this->sendContent();
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
    

    private function streamContent(): void
    {
        if ($this->disableStreaming) {
            echo $this->content;
            return;
        }

        $chunkSize = 8192; // 8KB chunks
        $length    = strlen($this->content);

        for ($i = 0; $i < $length; $i += $chunkSize) {
            echo substr($this->content, $i, $chunkSize);
            ob_flush();
            flush();

            // Prevent timeouts for very large content
            if (connection_aborted()) {
                break;
            }
        }
    }

    /**
     * Send only headers (used for streams/files).
     */
    public function sendHeaders(): void
    {
        if (! $this->areHeadersSent()) {
            foreach ($this->headers as $name => $value) {
                header("$name: $value", true);
            }
            $this->headersSent = true;
        }
    }

    /**
     * Check if header exists
     */
    public function hasHeader(string $name): bool
    {
        return array_key_exists($name, $this->headers);
    }
/**
 * Get header values as array
 */
    public function getHeaderLines(): array
    {
        return array_map(
            fn($name, $value) => "$name: $value",
            array_keys($this->headers),
            $this->headers
        );
    }

/**
 * Remove a header
 */
    public function removeHeader(string $name): self
    {
        unset($this->headers[$name]);
        return $this;
    }
    /**
     * Get the HTTP status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get a specific header value.
     */

    /**
     * Get all response headers
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    /**
     * getHeader
     *
     * @param  mixed $name
     * @return string
     */
    public function getHeader(string $name): ?string
    {
        $name = $this->normalizeHeaderName($name);
        return $this->headers[$name] ?? null;
    }
    /**
     * Set a header on the response.
     */
    
    /**
     * setHeader
     *
     * @param  mixed $name
     * @param  mixed $value
     * @return static
     */
    public function setHeader(string $name, string $value): static
    {
        if (! is_string($value)) {
            throw new \InvalidArgumentException(
                sprintf('Header value must be a string, %s given', gettype($value))
            );
        }

        $name                 = $this->normalizeHeaderName($name);
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Whether headers were already sent.
     */
    public function areHeadersSent(): bool
    {
        return $this->headersSent || headers_sent();
    }

    /**
     * Get JSON options.
     */
    public static function getJsonOptions(): int
    {
        return self::$jsonOptions;
    }

    /**
     * Set JSON options.
     */
    public static function setJsonOptions(int $options): void
    {
        self::$jsonOptions = $options;
    }

    /**
     * make
     *
     * @param  mixed $content
     * @param  mixed $status
     * @param  mixed $headers
     * @return static
     */
    public static function make(string $content = '', int $status = self::HTTP_OK, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    /**
     * Set the HTTP status code fluently.
     */    
    /**
     * status
     *
     * @param  mixed $code
     * @return static
     */
    public function status(int $code): static
    {
        $this->status = $code;
        return $this;
    }

/**
 * Fluent alias for setContent
 */    
    /**
     * content
     *
     * @param  mixed $content
     * @return self
     */
    public function content(string $content): self
    {
        $this->assertMemoryAvailable($content);
        $this->content = $content;
        return $this;
    }

    /**
     * Set multiple headers at once
     */    
    /**
     * withHeaders
     *
     * @param  mixed $headers
     * @return static
     */
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }
    
    /**
     * withRedirect
     *
     * @param  mixed $url
     * @param  mixed $status
     * @return static
     */
    public function withRedirect(string $url, int $status = 302): static
{
    return $this->setHeader('Location', $url)->status($status);
}
    /**
     * Remove a header
     */    
    /**
     * withoutHeader
     *
     * @param  mixed $name
     * @return static
     */
    public function withoutHeader(string $name): static
    {
        unset($this->headers[$name]);
        return $this;
    }
    
    /**
     * withText
     *
     * @param  mixed $text
     * @return self
     */
    public function withText(string $text): self
    {
        $this->content = $text;
        $this->setHeader('Content-Type', 'text/plain; charset=' . self::DEFAULT_CHARSET);
        return $this;
    }
    // Careminate\Http\Responses\Response.php
// Add these methods to the class:

/**
 * Get the response content
 */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the response content
     */

    public function setContent(string $content): self
    {
        return $this->content($content);
    }

     protected function sendContent(): void
    {
        if (!in_array($this->status, [204, 304])) {
            $this->streamContent();
        }
    }
    
    private function compressContent(): void
    {
        if (! empty($this->content)) {
            $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
            if (str_contains($acceptEncoding, 'gzip')) {
                $this->content = gzencode($this->content);
                $this->setHeader('Content-Encoding', 'gzip');
            } elseif (str_contains($acceptEncoding, 'deflate')) {
                $this->content = gzdeflate($this->content);
                $this->setHeader('Content-Encoding', 'deflate');
            }
        }
    }

    // New recommended methods
    public function withCache(int $maxAge = 3600, bool $public = true): static
    {
        return $this->setHeader('Cache-Control', sprintf(
            '%s, max-age=%d',
            $public ? 'public' : 'private',
            $maxAge
        ))->setHeader('ETag', hash('xxh128', $this->content));
    }

    public function withCsrfToken(): static
    {
        if (str_starts_with($this->getHeader('Content-Type') ?? '', 'text/html')) {
            $token         = bin2hex(random_bytes(32));
            $this->content = str_replace(
                '</form>',
                '<input type="hidden" name="_token" value="' . $token . '"></form>',
                $this->content
            );
        }
        return $this;
    }

    public function withRateLimit(int $limit, int $remaining, int $reset): static
    {
        return $this->withHeaders([
            'X-RateLimit-Limit'     => $limit,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset'     => $reset,
        ]);
    }

    public function withCsp(string $policy = "default-src 'self'"): static
    {
        return $this->setHeader('Content-Security-Policy', $policy);
    }

    public function isNotModified(array $requestHeaders): bool
    {
        $ifNoneMatch = $requestHeaders['If-None-Match'] ??
        $requestHeaders['HTTP_IF_NONE_MATCH'] ?? '';
        $ifModifiedSince = $requestHeaders['If-Modified-Since'] ??
        $requestHeaders['HTTP_IF_MODIFIED_SINCE'] ?? '';

        $etag         = $this->getHeader('ETag');
        $lastModified = strtotime($this->getHeader('Last-Modified')) ?: 0;

        if ((! empty($etag) && trim($ifNoneMatch, '"') === trim($etag, '"')) ||
            (! empty($lastModified) && strtotime($ifModifiedSince) >= $lastModified)) {
            $this->status = 304;
            $this->sendHeaders();
            return true;
        }

        return false;
    }

    public function toPsr7(): \Psr\Http\Message\ResponseInterface
    {
        $factory = new Psr17Factory();

        $response = $factory->createResponse($this->status)
            ->withBody($factory->createStream($this->content));

        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    public static function setJsonSerializer(callable $serializer): void
    {
        self::$jsonSerializer = \Closure::fromCallable($serializer);
    }

    public static function getJsonSerializer(): ?\Closure
    {
        return self::$jsonSerializer;
    }

    public function withJson(mixed $data, int $options = 0, int $depth = 512) : self
    {
        $options = $options ?: self::getJsonOptions();

        try {
            $serializer = self::getJsonSerializer();
            $content    = $serializer
            ? $serializer($data)
            : json_encode($data, $options, $depth);
        } catch (\JsonException $e) {
            throw new \JsonException('Failed to encode JSON: ' . $e->getMessage(), 0, $e);
        }

        $this->content = $content;
        $this->setHeader('Content-Type', 'application/json; charset=' . self::DEFAULT_CHARSET);
        return $this;
    }

    public function disableStreaming(): static
    {
        $this->disableStreaming = true;
        return $this;
    }

    // In your Response class
    public static function success(string $message = '', mixed $data = [], int $status = 200): static
    {
        return static::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message = '', mixed $errors = [], int $status = 400): static
    {
        return static::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    // Add this method for memory checks
    /**
     * Check if there's enough memory available
     */
    private function assertMemoryAvailable(string $content): void
    {
        $memoryLimit = ini_get('memory_limit');
        $limitBytes  = $this->convertToBytes($memoryLimit);

        // Calculate safe limit (80% of total memory)
        $safeLimit   = (int) ($limitBytes * 0.8);
        $contentSize = strlen($content);

        if ($contentSize > $safeLimit) {
            throw new \LengthException(sprintf(
                'Content length (%s) exceeds safe memory limit (%s of %s)',
                $this->formatBytes($contentSize),
                $this->formatBytes($safeLimit),
                $memoryLimit
            ));
        }
    }

    /**
     * Convert bytes to human-readable format
     */
    /**
     * Format bytes for human-readable output (returns string)
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    /**
     * Convert memory string (like '128M') to bytes
     */
    /**
     * Convert memory string (like '128M') to bytes (returns int)
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '-1') {
            return PHP_INT_MAX; // Unlimited memory
        }

        $unit  = strtolower($value[strlen($value) - 1]);
        $bytes = (int) $value;

        return match ($unit) {
            'g'     => $bytes * 1024 * 1024 * 1024,
            'm'     => $bytes * 1024 * 1024,
            'k'     => $bytes * 1024,
            default => $bytes,
        };
    }

/**
 * Add HTTP/2 server push header
 *
 * @param string $uri The resource to push
 * @param string $type The type of resource (script, style, image, etc.)
 * @return static
 */
    public function withPush(string $uri, string $type = 'script'): static
    {
        $validTypes = ['script', 'style', 'image', 'font', 'document'];
        $type       = strtolower($type);

        if (! in_array($type, $validTypes)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid push type "%s". Valid types are: %s',
                    $type,
                    implode(', ', $validTypes)
                )
            );
        }

        $linkHeader = $this->getHeader('Link') ?? '';
        $newLink    = sprintf('<%s>; rel=preload; as=%s', $uri, $type);

        if (! empty($linkHeader)) {
            $newLink = $linkHeader . ', ' . $newLink;
        }

        return $this->setHeader('Link', $newLink);
    }

/**
 * Add resource preload header
 *
 * @param string $uri The resource to preload
 * @param string $as The resource type (script, style, etc.)
 * @return static
 */
    public function withPreload(string $uri, string $as): static
    {
        $validTypes = ['script', 'style', 'image', 'font', 'fetch', 'document'];
        $as         = strtolower($as);

        if (! in_array($as, $validTypes)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid preload type "%s". Valid types are: %s',
                    $as,
                    implode(', ', $validTypes)
                )
            );
        }

        $linkHeader = $this->getHeader('Link') ?? '';
        $newLink    = sprintf('<%s>; rel=preload; as=%s', $uri, $as);

        if (! empty($linkHeader)) {
            $newLink = $linkHeader . ', ' . $newLink;
        }

        return $this->setHeader('Link', $newLink);
    }

/**
 * Add generic Link header
 *
 * @param string $uri The URI to link to
 * @param string $rel The relationship type
 * @return static
 */
    public function withLink(string $uri, string $rel): static
    {
        $linkHeader = $this->getHeader('Link') ?? '';
        $newLink    = sprintf('<%s>; rel="%s"', $uri, $rel);

        if (! empty($linkHeader)) {
            $newLink = $linkHeader . ', ' . $newLink;
        }

        return $this->setHeader('Link', $newLink);
    }

/**
 * Add multiple security headers at once
 *
 * @param array $headers Additional security headers to include
 * @return static
 */
    public function withSecurityHeaders(array $headers = []): static
    {
        $defaults = [
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'SAMEORIGIN',
            'X-XSS-Protection'        => '1; mode=block',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Permissions-Policy'      => 'geolocation=(self), microphone=()',
            'Content-Security-Policy' => "default-src 'self'",
        ];

        return $this->withHeaders(array_merge($defaults, $headers));
    }

    private function normalizeHeaderName(string $name): string
    {
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
    }

      protected function ensureNoOutputConflict(): void
    {
        if (headers_sent()) {
            throw new \RuntimeException('Headers already sent');
        }
    }

    
}
