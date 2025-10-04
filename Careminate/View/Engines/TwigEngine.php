<?php declare(strict_types=1);

namespace Careminate\View\Engines;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Careminate\View\Contracts\ViewInterface;

class TwigEngine implements ViewInterface
{
    protected Environment $twig;

    public function __construct(string $viewPath, string $cachePath)
    {
        $loader = new FilesystemLoader($viewPath);
        
        $this->twig = new Environment($loader, [
            'cache' => $cachePath,
            'auto_reload' => env('APP_ENV') === 'development',
            'debug' => env('APP_ENV') === 'development',
        ]);
    }

    public function render(string $template, array $parameters = []): string
    {
        // Convert dot notation to file path with .html.twig extension
        $templateFile = $this->resolveTemplatePath($template);
        
        return $this->twig->render($templateFile, $parameters);
    }

    protected function resolveTemplatePath(string $template): string
    {
        // Convert dot notation to directory structure and add .html.twig extension
        return str_replace('.', '/', $template) . '.html.twig';
    }

    public function addGlobal(string $key, mixed $value): void
    {
        $this->twig->addGlobal($key, $value);
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }
}