<?php declare (strict_types = 1);
namespace Careminate\Container;

use Exception;
use ReflectionClass;
use ReflectionException;
use Careminate\Providers\ServiceProvider;
use Careminate\Exceptions\ContainerException;
use Careminate\Container\Contracts\ContainerInterface;

class Container implements ContainerInterface
{
    protected array $bindings                = [];
    protected array $instances               = [];
    protected array $aliases                 = [];
    protected array $tags                    = [];
    protected array $resolvingCallbacks      = [];
    protected array $afterResolvingCallbacks = [];
    protected array $definitions             = [];
    protected array $contextual              = [];

    // Bind a service
    public function bind(string $abstract, callable | string | null $concrete = null): BindingDefinition
    {
        $definition                = new BindingDefinition($abstract, $concrete);
        $this->bindings[$abstract] = $definition;
        return $definition;
    }

    // OPTIONAL: Add this to your Container class if you want backward compatibility
    public function add(string $abstract, callable | string | null $concrete = null): void
    {
        $this->bind($abstract, $concrete);
    }

    // Bind a singleton
    public function singleton(string $abstract, callable | string | null $concrete = null): BindingDefinition
    {
        $definition                 = $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
        return $definition;
    }

    // Bind an already instantiated object
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    // Check if a binding exists
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }

    // Resolve a service
    public function make(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        if (array_key_exists($abstract, $this->instances) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        $this->runResolvingCallbacks($abstract);

        $definition = $this->bindings[$abstract] ?? new BindingDefinition($abstract);

        $concrete = $definition instanceof BindingDefinition
        ? $definition->getConcrete()
        : $definition;

        $arguments = $definition instanceof BindingDefinition
        ? $definition->getArguments()
        : [];

        $object = is_callable($concrete)
        ? $concrete($this, $parameters)
        : $this->build($concrete, [ ...$arguments, ...$parameters]);

        $this->runAfterResolvingCallbacks($abstract, $object);

        if (array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    // Build a class with auto-injected dependencies
    public function build(string $concrete, array $parameters = []): mixed
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ContainerException("Class [$concrete] does not exist.");
        }

        if (! $reflector->isInstantiable()) {
            throw new ContainerException("Class [$concrete] does not exist.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type === null || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve class dependency '{$parameter->name}'");
                }
            } else {
                // $dependencies[] = $this->make($type->getName());
                $paramType = $type->getName();

                // Check contextual override first
                if (isset($this->contextual[$concrete][$paramType])) {
                    $implementation = $this->contextual[$concrete][$paramType];
                    $dependencies[] = is_callable($implementation)
                    ? $implementation($this)
                    : $this->make($implementation);
                } else {
                    $dependencies[] = $this->make($paramType);
                }

            }
        }

        return $reflector->newInstanceArgs(array_merge($dependencies, $parameters));
    }

    // PSR-11 get
    public function get(string $id): mixed
    {
        return $this->make($id);
    }

    // Register alias
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    // Tag services
    public function tag(array | string $abstracts, string | array $tags): void
    {
        foreach ((array) $tags as $tag) {
            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    // Get tagged services
    public function tagged(string $tag): array
    {
        $results = [];

        foreach ($this->tags[$tag] ?? [] as $abstract) {
            $results[] = $this->make($abstract);
        }

        return $results;
    }

    // Before resolving
    public function resolving(string $abstract, callable $callback): void
    {
        $this->resolvingCallbacks[$abstract][] = $callback;
    }

    // After resolving
    public function afterResolving(string $abstract, callable $callback): void
    {
        $this->afterResolvingCallbacks[$abstract][] = $callback;
    }

    protected function runResolvingCallbacks(string $abstract): void
    {
        foreach ($this->resolvingCallbacks[$abstract] ?? [] as $callback) {
            $callback($this);
        }
    }

    protected function runAfterResolvingCallbacks(string $abstract, mixed $object): void
    {
        foreach ($this->afterResolvingCallbacks[$abstract] ?? [] as $callback) {
            $callback($object, $this);
        }
    }

    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    public function addContextualBinding(string $concrete, string $abstract, callable | string $implementation): void
    {
        $this->contextual[$concrete][$abstract] = $implementation;
    }

    public function registerProvider(ServiceProvider|string $provider): void
{
    $provider = is_string($provider) ? new $provider($this) : $provider;

    if (! $provider instanceof ServiceProvider) {
        throw new \InvalidArgumentException('Invalid service provider class.');
    }

    $provider->register();
    $provider->boot();
}

public function registerProviders(array $providers): void
{
    foreach ($providers as $provider) {
        $this->registerProvider($provider);
    }
}
}
