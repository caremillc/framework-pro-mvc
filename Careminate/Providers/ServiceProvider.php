<?php declare(strict_types=1);
namespace Careminate\Providers;

use Careminate\Container\ServiceContainer;

abstract class ServiceProvider
{
    protected ServiceContainer $app;

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
    }

    abstract public function register(): void;

    public function boot(): void
    {
        // Optionally overridden by subclasses
    }
}