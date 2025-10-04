<?php declare(strict_types=1);

namespace Careminate\View\Engines;

use Careminate\View\Contracts\ViewInterface;

class FlintEngine implements ViewInterface
{
    protected FlintCompiler $compiler;
    protected array $globals = [];

    public function __construct(FlintCompiler $compiler)
    {
        $this->compiler = $compiler;
    }

    public function render(string $template, array $parameters = []): string
    {
        // Reset the template engine for each render
        $engine = $this->compiler->getEngine();
        $engine->reset();
        
        // Merge globals with template parameters
        $data = array_merge($this->globals, $parameters);

        // Extract variables for the template
        extract($data);

        // Start output buffering
        ob_start();

        try {
            // Include the compiled template
            $compiledFile = $this->compiler->compile($template, $data);
            
            // Make engine available to the template
            $__engine = $engine;
            
            include $compiledFile;
            
            $content = ob_get_clean();
            
            // If a layout was specified, render it with the sections
            if ($engine->getLayout()) {
                return $this->renderLayout($engine->getLayout(), $engine->getSections(), $data);
            }
            
            return $content;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    protected function renderLayout(string $layout, array $sections, array $data): string
    {
        // Reset engine for layout
        $engine = $this->compiler->getEngine();
        $engine->reset();
        
        // Set sections for layout
        foreach ($sections as $name => $content) {
            $engine->startSection($name);
            echo $content;
            $engine->endSection();
        }
        
        // Extract variables
        extract($data);
        
        // Start output buffering for layout
        ob_start();
        
        try {
            $compiledFile = $this->compiler->compile($layout, $data);
            $__engine = $engine;
            include $compiledFile;
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public function addGlobal(string $key, mixed $value): void
    {
        $this->globals[$key] = $value;
    }

    public function getCompiler(): FlintCompiler
    {
        return $this->compiler;
    }
}


