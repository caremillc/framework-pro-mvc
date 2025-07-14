<?php declare(strict_types=1);

namespace Careminate\Routing;

class PendingResourceRegistration
{
    protected string $name;
    protected string $controller;
    protected array $only = [];
    protected array $except = [];
    protected array $middleware = [];
    protected array $names = [];

    public function __construct(string $name, string $controller)
    {
        $this->name = trim($name, '/');
        $this->controller = $controller;
    }

    public function only(array $methods): static
    {
        $this->only = $methods;
        return $this;
    }

    public function except(array $methods): static
    {
        $this->except = $methods;
        return $this;
    }

    public function middleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function names(array $names): static
    {
        $this->names = $names;
        return $this;
    }

    public function register(): void
    {
        $routes = [
            'index'   => ['GET',    $this->name,                     'index'],
            'create'  => ['GET',    "{$this->name}/create",         'create'],
            'store'   => ['POST',   $this->name,                     'store'],
            'show'    => ['GET',    "{$this->name}/{id}/show",        'show'],
            'edit'    => ['GET',    "{$this->name}/{id}/edit",         'edit'],
            'update'  => ['PUT',    "{$this->name}/{id}/update",     'update'],
            'destroy' => ['DELETE', "{$this->name}/{id}/delete",    'destroy'],
        ];

        foreach ($routes as $method => [$verb, $uri, $action]) {
            if (!empty($this->only) && !in_array($method, $this->only, true)) {
                continue;
            }

            if (!empty($this->except) && in_array($method, $this->except, true)) {
                continue;
            }

            Route::add($verb, $uri, $this->controller, $action, $this->middleware);
        }
    }
}


