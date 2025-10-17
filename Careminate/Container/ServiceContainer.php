<?php declare(strict_types=1);
namespace Careminate\Container;

class ServiceContainer
{
    protected array $bindings = [];
    protected array $instances = [];

    public function bind(string $key, callable $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    public function singleton(string $key, callable $resolver): void
    {
        $this->bindings[$key] = $resolver;
        $this->instances[$key] = null; // lazy-loaded later
    }

    public function make(string $key)
    {
        // If instance already exists (singleton)
        if (array_key_exists($key, $this->instances) && $this->instances[$key] !== null) {
            return $this->instances[$key];
        }

        // Resolve the binding
        if (!isset($this->bindings[$key])) {
            throw new \Exception("Service '{$key}' not bound to container.");
        }

        $object = call_user_func($this->bindings[$key], $this);

        // Cache singleton instance
        if (array_key_exists($key, $this->instances)) {
            $this->instances[$key] = $object;
        }

        return $object;
    }
}
