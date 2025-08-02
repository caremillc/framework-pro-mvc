<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Contracts;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface
{
    public function send(): void;
    public function sendHeaders(): void;
    public function getStatus(): int;
    public function getHeader(string $key): ?string;
    public function setHeader(string $key, string $value): static;
    public function areHeadersSent(): bool;
    public function getContent(): string;
    public function setContent(string $content): static;
    public function content(string $content): static;
    
    // HTTP/2 and modern features
    public function withPush(string $uri, string $type = 'script'): static;
    public function withPreload(string $uri, string $as): static;
    public function withLink(string $uri, string $rel): static;
    
    // Security headers
    public function withSecurityHeaders(array $headers = []): static;
    // Caching
   
    public function isNotModified(array $requestHeaders): bool;
    // PSR-7 compatibility
    public function toPsr7(): PsrResponseInterface;
}