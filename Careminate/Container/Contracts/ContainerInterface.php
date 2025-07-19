<?php declare(strict_types=1);
namespace Careminate\Container\Contracts;

use Careminate\Container\BindingDefinition;

interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    //public function bind(string $abstract, callable|string|null $concrete = null): void;
    public function bind(string $abstract, callable | string | null $concrete = null): BindingDefinition;
    //public function singleton(string $abstract, callable|string|null $concrete = null): void;
    public function singleton(string $abstract, callable | string | null $concrete = null): BindingDefinition;
    public function instance(string $abstract, mixed $instance): void;
    public function make(string $abstract, array $parameters = []): mixed;
    public function get(string $id): mixed;
    public function alias(string $abstract, string $alias): void;
    public function tag(array|string $abstracts, string|array $tags): void;
    public function tagged(string $tag): array;
    public function resolving(string $abstract, callable $callback): void;
    public function afterResolving(string $abstract, callable $callback): void;
}
