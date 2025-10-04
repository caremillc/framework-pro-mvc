<?php declare(strict_types=1);
namespace Careminate\View\Contracts;

interface ViewInterface
{
    public function render(string $template, array $parameters = []): string;
    public function addGlobal(string $key, mixed $value): void;
}


