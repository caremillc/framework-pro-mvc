<?php declare(strict_types=1);

namespace Careminate\Providers;

use Careminate\View\ViewEngines\ViewEngine;
use Careminate\View\ViewEngines\BladeLikeCompiler;

class ViewServiceProvider
{
    protected ?ViewEngine $engine = null;

    public function register(): void
    {
        $viewsPath = BASE_PATH . '/resources/views';
        $cachePath = BASE_PATH . '/storage/cache/views';

        // Ensure cache directory exists
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        $compiler = new BladeLikeCompiler($viewsPath, $cachePath);
        $this->engine = new ViewEngine($compiler);
    }

    public function engine(): ViewEngine
    {
        if (!$this->engine) {
            throw new \RuntimeException('View engine not initialized in ViewServiceProvider.');
        }

        return $this->engine;
    }
}
