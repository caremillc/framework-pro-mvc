<?php declare(strict_types=1);

namespace Careminate\Container;

class BindingDefinition
{
    protected string $abstract;
    protected mixed $concrete;
    protected array $arguments = [];

    public function __construct(string $abstract, mixed $concrete = null)
    {
        $this->abstract = $abstract;
        $this->concrete = $concrete ?? $abstract;
    }

    public function addArgument(string $class): static
    {
        $this->arguments[] = $class;
        return $this;
    }

    public function getAbstract(): string
    {
        return $this->abstract;
    }

    public function getConcrete(): mixed
    {
        return $this->concrete;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
