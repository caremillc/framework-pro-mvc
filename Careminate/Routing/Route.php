<?php declare(strict_types=1);
namespace Careminate\Routing;

class Route
{
    public string $method;
    public string $uri;
    public $action;
    public ?string $name = null;
    public array $middleware = [];

     public function __construct(string $method, string $uri, $action)
    {
        $this->method = strtoupper($method);
        $this->uri = '/' . trim($uri, '/');
        if ($this->uri === '//') $this->uri = '/';
        $this->action = $action;
    }

     public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(array|string $middleware): static
    {
        $this->middleware = is_array($middleware) ? $middleware : [$middleware];
        return $this;
    }

}