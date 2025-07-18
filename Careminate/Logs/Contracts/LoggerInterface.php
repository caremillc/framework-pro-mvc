<?php declare(strict_types=1);
namespace Careminate\Logs\Contracts;

interface LoggerInterface
{
    public function log(string $message): void;
}