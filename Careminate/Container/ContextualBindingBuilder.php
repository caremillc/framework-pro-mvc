<?php declare(strict_types=1);

namespace Careminate\Container;

class ContextualBindingBuilder
{
    protected Container $container;
    protected string $concrete;
    protected string $abstract;

    public function __construct(Container $container, string $concrete)
    {
        $this->container = $container;
        $this->concrete = $concrete;
    }

    public function needs(string $abstract): static
    {
        $this->abstract = $abstract;
        return $this;
    }

    public function give(callable|string $implementation): void
    {
        $this->container->addContextualBinding($this->concrete, $this->abstract, $implementation);
    }
}
