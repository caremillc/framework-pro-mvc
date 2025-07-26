<?php declare (strict_types = 1);

namespace Careminate\Http\Responses;

use Careminate\Http\Responses\Contracts\ResponseInterface;
use Careminate\Http\Responses\Traits\HandlesFileResponses;
use Careminate\Http\Responses\Traits\HandlesJsonResponses;
use Careminate\Http\Responses\Traits\HandlesTextResponses;
use Careminate\Http\Responses\Traits\HandlesThrowableResponses;
use Careminate\Http\Responses\Traits\HandlesXmlResponses;
use Careminate\Support\Traits\Macroable;

class Response implements ResponseInterface
{
    use HandlesJsonResponses;
    use HandlesXmlResponses;
    use HandlesTextResponses;
    use HandlesFileResponses;
    use HandlesThrowableResponses;
    use Macroable;

    public const DEFAULT_CHARSET = 'UTF-8';

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
     * Send headers and content to the browser.
     */
    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("$name: $value", true);
        }

        if (! in_array($this->status, [204, 304]) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            echo $this->content;
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
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
     * Get the HTTP status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get a specific header value.
     */
    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Set a header on the response.
     */
    public function setHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
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
    public function status(int $code): static
    {
        $this->status = $code;
        return $this;
    }

/**
 * Fluent alias for setContent
 */
    public function content(string $content): static
    {
        return $this->setContent($content);
    }
    /**
     * Set multiple headers fluently.
     */
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
        return $this;
    }

    /**
     * Set response as JSON fluently.
     */
    public function withJson(mixed $data, int $options = 0): static
    {
        $options = $options ?: self::getJsonOptions();

        try {
            $this->content = json_encode($data, $options);
        } catch (\JsonException $e) {
            throw new \JsonException('Failed to encode JSON: ' . $e->getMessage(), 0, $e);
        }

        $this->setHeader('Content-Type', 'application/json; charset=' . self::DEFAULT_CHARSET);
        return $this;
    }

    /**
     * Set plain text response fluently.
     */
    public function withText(string $text): static
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
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

}
