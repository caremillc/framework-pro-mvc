<?php declare(strict_types=1);
namespace Careminate\Http\Controllers;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;

abstract class AbstractController
{
    protected ?ContainerInterface $container = null;
    protected Request $request;
  
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }
    
    public function setContainer(ContainerInterface $container): void
    {
        // Store the container instance for use within the controller.
        $this->container = $container;
    }

   
   /**
     * Render a Twig template using dot notation.
     *
     * @param string $template  Template name in dot notation, e.g., "posts.index"
     * @param array $parameters Parameters to pass to the template
     * @param Response|null $response Optional response object
     * @return Response
     */
    public function render(string $template, array $parameters = [], ?Response $response = null): Response
    {
        if (!$this->container) {
            throw new \RuntimeException('Container is not set.');
        }

        // Convert dot notation to slash notation
        $templatePath = str_replace('.', '/', $template) . '.html.twig';

        // Render the template using Twig
        $content = $this->container->get('twig')->render($templatePath, $parameters);

        // Use existing response or create new
        $response ??= new Response();
        $response->setContent($content);

        return $response;
    }
}

