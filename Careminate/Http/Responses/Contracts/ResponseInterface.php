<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Contracts;

interface ResponseInterface
{
    public function send(): void;
    public function sendHeaders(): void;
    public function getStatus(): int;
    public function getHeader(string $key): ?string;
    public function setHeader(string $key, string $value): static;
    public function areHeadersSent(): bool;
    
    // Add these new methods
    public function getContent(): string;
    public function setContent(string $content): static;
    public function content(string $content): static; // Fluent alias
}