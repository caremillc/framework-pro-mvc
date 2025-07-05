<?php declare(strict_types=1);
namespace Careminate\Container;

use Careminate\Container\ContainerInterface;
use Careminate\Container\Exceptions\ContainerException;
use Exception;
use ReflectionClass;
use ReflectionException;

class Container implements ContainerInterface
{
    protected array $bindings                = [];
    protected array $instances               = [];
    protected array $aliases                 = [];
    protected array $tags                    = [];
    protected array $resolvingCallbacks      = [];
    protected array $afterResolvingCallbacks = [];

    // Bind a service
    public function bind(string $abstract, callable | string | null $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    // OPTIONAL: Add this to your Container class if you want backward compatibility
    public function add(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete);
    }

    // Bind a singleton
    public function singleton(string $abstract, callable | string | null $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
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

        // Return existing singleton
        if (array_key_exists($abstract, $this->instances) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        $this->runResolvingCallbacks($abstract);

        // Resolve via binding or auto-wiring
        $concrete = $this->bindings[$abstract] ?? $abstract;
        $object   = is_callable($concrete) ? $concrete($this, $parameters) : $this->build($concrete, $parameters);

        $this->runAfterResolvingCallbacks($abstract, $object);

        // Save singleton if registered
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
                $dependencies[] = $this->make($type->getName());
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
}
