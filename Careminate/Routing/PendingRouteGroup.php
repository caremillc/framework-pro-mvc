<?php declare(strict_types=1);

namespace Careminate\Routing;

class PendingRouteGroup
{
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function prefix(string $prefix): static
    {
        $this->attributes['prefix'] = trim($prefix, '/');
        return $this;
    }

    public function middleware(array $middleware): static
    {
        $this->attributes['middleware'] = $middleware;
        return $this;
    }

    public function group(\Closure $callback): void
    {
        Route::startGroup($this->attributes);
        $callback();
        Route::endGroup();
    }
}
