<?php declare(strict_types=1);

namespace Careminate\View;

class ViewManager
{
    protected string $viewsPath;
    protected string $cachePath;

    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
    }

    public function render(string $view, array $data = []): string
    {
        $viewFile = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.caremi.php';
// dd($viewFile);
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View [{$view}] not found at {$viewFile}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;
        return ob_get_clean();
    }
}
