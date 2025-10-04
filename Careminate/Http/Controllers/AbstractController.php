<?php declare(strict_types=1);

namespace Careminate\Http\Controllers;

use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use Careminate\View\Factories\ViewFactory;

abstract class AbstractController
{
    protected ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Render a view using the view factory
     */
    protected function render(string $template, array $parameters = [], ?Response $response = null): Response
    {
        $content = $this->container->get('view')->make($template, $parameters);
        
        $response ??= new Response();
        $response->setContent($content);

        return $response;
    }

    /**
     * Alias for render() - provides consistency with helper function
     */
  protected function view(string $view, array $data = [], ?string $engine = null): Response
{
    // If no engine specified, use the default
    if ($engine === null) {
        $engine = env('VIEW_ENGINE', 'flint');
    }
    
    // Optional: Add automatic fallback logic
    try {
        $content = $this->container->get('view')->make($view, $data, $engine);
    } catch (\Exception $e) {
        // Fallback to the other engine
        $fallbackEngine = $engine === 'flint' ? 'twig' : 'flint';
        $content = $this->container->get('view')->make($view, $data, $fallbackEngine);
    }
    
    return new Response($content);
}

    /**
     * Redirect to a different URL
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $status = 200, array $headers = []): Response
    {
        $headers['Content-Type'] = 'application/json';
        
        return new Response(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            $status,
            $headers
        );
    }

    /**
     * Get a service from the container
     */
    protected function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Check if a service exists in the container
     */
    protected function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Add flash message to session
     */
    protected function flash(string $type, string $message): void
    {
        $session = $this->get('session');
        if (method_exists($session, 'flash')) {
            $session->flash($type, $message);
        }
    }

    /**
     * Get the request object
     */
    protected function request()
    {
        return $this->get('request');
    }

    /**
     * Get the session object
     */
    protected function session()
    {
        return $this->get('session');
    }
}