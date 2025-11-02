<?php declare(strict_types=1);
namespace Careminate\Providers;

interface ServiceProvider
{
    public function register(): void;
}
