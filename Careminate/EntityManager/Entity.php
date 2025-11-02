<?php declare(strict_types=1);

namespace Careminate\EntityManager;

use DateTimeImmutable;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

abstract class Entity
{
    protected bool $immutable = false;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Hydrate properties safely with type conversion.
     */
    public function fill(array $data): void
    {
        $reflection = new ReflectionClass($this);

        foreach ($data as $key => $value) {
            if (!$reflection->hasProperty($key)) {
                continue;
            }

            $property = $reflection->getProperty($key);
            $type = $property->getType();

            // Allow nulls
            if ($value === null) {
                $property->setAccessible(true);
                $property->setValue($this, null);
                continue;
            }

            // Type cast based on property type
            if ($type) {
                $typeName = $type->getName();

                switch ($typeName) {
                    case 'int':    $value = (int) $value; break;
                    case 'float':  $value = (float) $value; break;
                    case 'bool':   $value = (bool) $value; break;
                    case 'string': $value = (string) $value; break;
                    case DateTimeImmutable::class:
                        if (!$value instanceof DateTimeImmutable) {
                            $value = new DateTimeImmutable((string) $value);
                        }
                        break;
                }
            }

            $property->setAccessible(true);
            $property->setValue($this, $value);
        }
    }

    /**
     * Convert the entity to an associative array.
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $props = $reflection->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);

        $data = [];
        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($this);

            if ($value instanceof DateTimeImmutable) {
                $value = $value->format('Y-m-d H:i:s');
            }

            $data[$prop->getName()] = $value;
        }

        return $data;
    }

    /**
     * Magic getter (supports $entity->property and $entity->getProperty())
     */
    public function __get(string $name)
    {
        $method = 'get' . ucfirst($name);

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        if (property_exists($this, $name)) {
            $prop = new ReflectionProperty($this, $name);
            $prop->setAccessible(true);
            return $prop->getValue($this);
        }

        throw new RuntimeException("Undefined property or getter: {$name}");
    }

    /**
     * Magic setter (supports $entity->property = value and $entity->setProperty())
     */
    public function __set(string $name, $value): void
    {
        if ($this->immutable) {
            throw new RuntimeException('Entity is immutable.');
        }

        $method = 'set' . ucfirst($name);

        if (method_exists($this, $method)) {
            $this->{$method}($value);
            return;
        }

        if (property_exists($this, $name)) {
            $prop = new ReflectionProperty($this, $name);
            $prop->setAccessible(true);
            $prop->setValue($this, $value);
            return;
        }

        throw new RuntimeException("Undefined property or setter: {$name}");
    }

    /**
     * Check if a property exists dynamically.
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name);
    }

    /**
     * Make the entity immutable (optional)
     */
    public function makeImmutable(): void
    {
        $this->immutable = true;
    }

    /**
     * Convert to JSON for API or debugging.
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $flags);
    }
}
