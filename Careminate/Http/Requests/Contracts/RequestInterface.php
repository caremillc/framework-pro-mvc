<?php declare(strict_types=1);

namespace Careminate\Http\Requests\Contracts;

interface RequestInterface
{
    public static function createFromGlobals(): self;

    public function getMethod(): string;
    public function getUri(): string;
    public function getPathInfo(): string;
    
    public function header(string $name): ?string;
    public function headers(): array;
    public function hasHeader(string $name): bool;
    public function getHeader(string $name): ?string;
    
    public function fullUrl(): string;
    public function isSecure(): bool;
    
    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;
    public function all(): array;
    
    public function input(string $key, mixed $default = null): mixed;
    public function query(string $key, mixed $default = null): mixed;
    public function post(string $key, mixed $default = null): mixed;
    
    public function cookie(string $key, mixed $default = null): mixed;
    
    public function file(string $key): ?array;
    public function hasFile(string $key): bool;
    public function allFiles(): array;
    
    public function server(string $key, mixed $default = null): mixed;
    public function getRawInput(): string;
    
    public function isJson(): bool;
    public function wantsJson(): bool;
    
    public function ip(): string;
    public function userAgent(): ?string;
    
    public function only(array|string $keys): array;
    public function except(array|string $keys): array;
    
    public function isMethod(string $method): bool;
    public function isPost(): bool;
    public function isGet(): bool;
    public function isPut(): bool;
    public function isPatch(): bool;
    public function isDelete(): bool;
    public function isHead(): bool;
    public function isOptions(): bool;
    
    public function prefersContentType(string $contentType): bool;
    public function acceptsHtml(): bool;
}
