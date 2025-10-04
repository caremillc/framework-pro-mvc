<?php declare(strict_types=1);

namespace Careminate\View\Factories;

use Careminate\View\Engines\FlintEngine;
use Careminate\View\Engines\TwigEngine;
use Careminate\View\Contracts\ViewInterface;

class ViewFactory
{
    protected array $engines = [];
    protected string $defaultEngine;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultEngine = $config['engine'] ?? 'flint';
    }

    public function registerEngine(string $name, ViewInterface $engine): void
    {
        $this->engines[$name] = $engine;
    }

    public function make(string $view, array $data = [], ?string $engine = null): string
    {
        $engineName = $engine ?? $this->defaultEngine;
        
        if (!isset($this->engines[$engineName])) {
            throw new \RuntimeException("View engine '{$engineName}' not registered.");
        }

        return $this->engines[$engineName]->render($view, $data);
    }

    public function addGlobal(string $key, mixed $value): void
    {
        foreach ($this->engines as $engine) {
            $engine->addGlobal($key, $value);
        }
    }

    public function getEngine(string $name): ?ViewInterface
    {
        return $this->engines[$name] ?? null;
    }
}