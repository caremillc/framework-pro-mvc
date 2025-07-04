<?php declare (strict_types = 1);

namespace Careminate\Http\Responses;

class Response
{
    public const HTTP_STATUS_TEXTS = [
        // 1xx
        100 => 'Continue', 101               => 'Switching Protocols', 102             => 'Processing', 103           => 'Early Hints',
        // 2xx
        200 => 'OK', 201                     => 'Created', 202                         => 'Accepted', 203             => 'Non-Authoritative Information',
        204 => 'No Content', 205             => 'Reset Content', 206                   => 'Partial Content', 207      => 'Multi-Status',
        208 => 'Already Reported', 226       => 'IM Used',
        // 3xx
        300 => 'Multiple Choices', 301       => 'Moved Permanently', 302               => 'Found', 303                => 'See Other',
        304 => 'Not Modified', 305           => 'Use Proxy', 307                       => 'Temporary Redirect', 308   => 'Permanent Redirect',
        // 4xx
        400 => 'Bad Request', 401            => 'Unauthorized', 402                    => 'Payment Required', 403     => 'Forbidden',
        404 => 'Not Found', 405              => 'Method Not Allowed', 406              => 'Not Acceptable', 407       => 'Proxy Authentication Required',
        408 => 'Request Timeout', 409        => 'Conflict', 410                        => 'Gone', 411                 => 'Length Required',
        412 => 'Precondition Failed', 413    => 'Payload Too Large', 414               => 'URI Too Long',
        415 => 'Unsupported Media Type', 416 => 'Range Not Satisfiable', 417           => 'Expectation Failed',
        418 => "I'm a teapot", 421           => 'Misdirected Request', 422             => 'Unprocessable Entity', 423 => 'Locked',
        424 => 'Failed Dependency', 425      => 'Too Early', 426                       => 'Upgrade Required', 428     => 'Precondition Required',
        429 => 'Too Many Requests', 431      => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
        // 5xx
        500 => 'Internal Server Error', 501  => 'Not Implemented', 502                 => 'Bad Gateway', 503          => 'Service Unavailable',
        504 => 'Gateway Timeout', 505        => 'HTTP Version Not Supported', 506      => 'Variant Also Negotiates',
        507 => 'Insufficient Storage', 508   => 'Loop Detected', 510                   => 'Not Extended', 511         => 'Network Authentication Required',
    ];

    // Common codes
    public const HTTP_OK                    = 200;
    public const HTTP_CREATED               = 201;
    public const HTTP_NO_CONTENT            = 204;
    public const HTTP_FOUND                 = 302;
    public const HTTP_NOT_MODIFIED          = 304;
    public const HTTP_NOT_FOUND             = 404;
    public const HTTP_BAD_REQUEST           = 400;
    public const HTTP_UNAUTHORIZED          = 401;
    public const HTTP_FORBIDDEN             = 403;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    private const DEFAULT_CHARSET      = 'UTF-8';
    private const DEFAULT_JSON_OPTIONS = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    private string $content = '';
    private int $status;
    private array $headers           = [];
    private bool $headersSent        = false;
    private bool $useOutputBuffering = true;

    private static int $jsonOptions = self::DEFAULT_JSON_OPTIONS;

    public function __construct(string $content = '', int $status = self::HTTP_OK, array $headers = [])
    {
        $this->setContent($content);
        $this->setStatus($status);
        $this->setHeaders($headers);
    }

    public function setHeader(string $name, string | int $value): static
    {
        $this->headers[$this->normalizeHeaderName($name)] = (string) $value;
        return $this;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$this->normalizeHeaderName($name)] ?? null;
    }

    public function removeHeader(string $name): static
    {
        unset($this->headers[$this->normalizeHeaderName($name)]);
        return $this;
    }

    public function setHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function areHeadersSent(): bool
    {
        return $this->headersSent;
    }

    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        return $clone->setHeader($name, $value);
    }

    public function withStatus(int $status): static
    {
        $clone = clone $this;
        return $clone->setStatus($status);
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setStatus(int $status): static
    {
        if (! isset(self::HTTP_STATUS_TEXTS[$status])) {
            throw new \InvalidArgumentException("Invalid HTTP status code: $status");
        }
        $this->status = $status;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getStatusText(): string
    {
        return self::HTTP_STATUS_TEXTS[$this->status] ?? '';
    }

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isRedirection(): bool
    {
        return $this->status >= 300 && $this->status < 400;
    }

    public function isClientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    public function isServerError(): bool
    {
        return $this->status >= 500 && $this->status < 600;
    }

    public function send(): void
    {
        if ($this->headersSent) {
            return;
        }

        ob_start();

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $this->content;

        $this->headersSent = true;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ob_end_flush();
        }
    }

    public static function json(array $data, int $status = self::HTTP_OK): static
    {
        return new static(
            json_encode($data, self::$jsonOptions),
            $status,
            ['Content-Type' => 'application/json; charset=' . self::DEFAULT_CHARSET]
        );
    }

    public static function html(string $html, int $status = self::HTTP_OK, array $headers = []): static
    {
        return new static($html, $status, array_merge([
            'Content-Type' => 'text/html; charset=' . self::DEFAULT_CHARSET,
        ], $headers));
    }

    public static function text(string $text, int $status = self::HTTP_OK, array $headers = []): static
    {
        return new static($text, $status, array_merge([
            'Content-Type' => 'text/plain; charset=' . self::DEFAULT_CHARSET,
        ], $headers));
    }

    public function redirect(string $url, int $status = self::HTTP_FOUND): static
    {
        return $this->setStatus($status)->setHeader('Location', $url)->setContent('');
    }

    public static function notFound(string $message = 'Not Found'): static
    {
        return static::text($message, self::HTTP_NOT_FOUND);
    }

    public static function badRequest(string $message = 'Bad Request'): static
    {
        return static::text($message, self::HTTP_BAD_REQUEST);
    }

    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return static::text($message, self::HTTP_UNAUTHORIZED);
    }

    public static function forbidden(string $message = 'Forbidden'): static
    {
        return static::text($message, self::HTTP_FORBIDDEN);
    }

    public static function serverError(string $message = 'Internal Server Error'): static
    {
        return static::text($message, self::HTTP_INTERNAL_SERVER_ERROR);
    }

    public static function noContent(array $headers = []): static
    {
        return new static('', self::HTTP_NO_CONTENT, $headers);
    }

    public function setOutputBuffering(bool $enabled): static
    {
        $this->useOutputBuffering = $enabled;
        return $this;
    }

    public static function setJsonOptions(int $options): void
    {
        self::$jsonOptions = $options;
    }

    private function normalizeHeaderName(string $name): string
    {
        return str_replace('_', '-', ucwords(strtolower($name), '-'));
    }

    public static function download(string $filePath, ?string $filename = null, array $headers = []): static
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("File not found: $filePath");
        }

        $filename ??= basename($filePath);

        return new static(file_get_contents($filePath), self::HTTP_OK, array_merge([
            'Content-Description'       => 'File Transfer',
            'Content-Type'              => mime_content_type($filePath),
            'Content-Disposition'       => 'attachment; filename="' . $filename . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => '0',
            'Cache-Control'             => 'must-revalidate',
            'Pragma'                    => 'public',
            'Content-Length'            => (string) filesize($filePath),
        ], $headers));
    }

    public static function stream(callable $callback, int $status = self::HTTP_OK, array $headers = []): static
    {
        $response = new static('', $status, array_merge([
            'Content-Type'      => 'application/octet-stream',
            'Transfer-Encoding' => 'chunked',
        ], $headers));

        ob_end_flush();
        flush();
        $callback();
        return $response;
    }

    public static function xml(array | string $data, int $status = self::HTTP_OK, array $headers = []): static
    {
        $xmlContent = is_array($data) ? self::toXml($data) : $data;

        return new static($xmlContent, $status, array_merge([
            'Content-Type' => 'application/xml; charset=' . self::DEFAULT_CHARSET,
        ], $headers));
    }

    protected static function toXml(array $data,  ? \SimpleXMLElement $xml = null) : string
    {
        $xml ??= new \SimpleXMLElement('<?xml version="1.0"?><response/>');
        foreach ($data as $key => $value) {
            is_array($value)
            ? self::toXml($value, $xml->addChild(is_numeric($key) ? "item$key" : $key))
            : $xml->addChild(is_numeric($key) ? "item$key" : $key, htmlspecialchars((string) $value));
        }
        return $xml->asXML();
    }

    public static function fromThrowable(\Throwable $e): static
    {
        $isDebug = env('APP_DEBUG', false);

        $errorData = [
            'error'   => true,
            'message' => $e->getMessage(),
        ];

        if ($isDebug) {
            $errorData += [
                'code'  => $e->getCode(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ];
        }

        return self::json($errorData, self::HTTP_INTERNAL_SERVER_ERROR);
    }

    public static function success(string $message = 'Success', array $data = [], int $status = self::HTTP_OK): static
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message = 'An error occurred', array $errors = [], int $status = self::HTTP_BAD_REQUEST): static
    {
        return self::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

}
