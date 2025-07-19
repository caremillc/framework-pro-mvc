<?php  declare (strict_types = 1);
namespace Careminate\Providers;

use Careminate\Container\Container;

abstract class ServiceProvider
{
    protected Container $app;

    // public function __construct(Container $app)
    // {
    //     $this->app = $app;
    // }
    public function __construct(Container $app)
    {
        if (! $app instanceof Container) {
            throw new \InvalidArgumentException('A valid Container instance must be passed to ServiceProvider.');
        }

        $this->app = $app;
    }

    public function boot(): void {}
    abstract public function register(): void;
}
