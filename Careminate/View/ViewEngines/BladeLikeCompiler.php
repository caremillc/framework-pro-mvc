<?php declare(strict_types=1);

namespace Careminate\View\ViewEngines;

/**
 * BladeLikeCompiler
 * -----------------
 * A custom template compiler inspired by Laravel Blade.
 * Supports .caremi.php and .php files, directive parsing,
 * cache management, and recompile control.
 */
class BladeLikeCompiler
{
    protected string $viewsPath;
    protected string $cachePath;
    protected bool $alwaysRecompile = false;
    protected array $verbatimBlocks = [];
    protected array $supportedExtensions = ['.caremi.php', '.php'];

    /**
     * Constructor
     */
    public function __construct(string $viewsPath, string $cachePath, bool $alwaysRecompile = false)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->alwaysRecompile = $alwaysRecompile;

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    /**
     * Compile a view into a cached PHP file.
     */
    public function compile(string $view): string
    {
        $viewPath = str_replace('.', '/', $view);
        $viewFile = $this->locateViewFile($viewPath);

        $compiledFile = $this->getCompiledPath($viewFile);

        if (!file_exists($compiledFile) || $this->isExpired($viewFile, $compiledFile)) {
            $contents = file_get_contents($viewFile);
            if ($contents === false) {
                throw new \RuntimeException("Unable to read view file: {$viewFile}");
            }

            $compiled = $this->compileString($contents);
            file_put_contents($compiledFile, $compiled);
        }

        return $compiledFile;
    }

    /**
     * Locate the view file with supported extensions.
     */
    protected function locateViewFile(string $viewPath): string
    {
        foreach ($this->supportedExtensions as $ext) {
            $file = "{$this->viewsPath}/{$viewPath}{$ext}";
            if (file_exists($file)) {
                return $file;
            }
        }

        $supported = implode(', ', $this->supportedExtensions);
        throw new \RuntimeException("View [{$viewPath}] not found. Supported extensions: {$supported}");
    }

    /**
     * Compile the raw template string.
     */
    protected function compileString(string $value): string
    {
        $value = $this->compileVerbatim($value);
        $value = $this->compileEchos($value);
        $value = $this->compileDirectives($value);
        return $value;
    }

    /**
     * Handle @verbatim blocks.
     */
    protected function compileVerbatim(string $value): string
    {
        if (preg_match_all('/@verbatim(.*?)@endverbatim/s', $value, $matches)) {
            foreach ($matches[0] as $i => $raw) {
                $placeholder = "__VERBATIM_BLOCK_{$i}__";
                $this->verbatimBlocks[$placeholder] = $matches[1][$i];
                $value = str_replace($raw, $placeholder, $value);
            }
        }
        return $value;
    }

    /**
     * Handle echo statements: {{ }} and {!! !!}
     */
    protected function compileEchos(string $value): string
    {
        $value = preg_replace('/{{\s*(.+?)\s*}}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, "UTF-8"); ?>', $value);
        $value = preg_replace('/{!!\s*(.+?)\s*!!}/', '<?php echo $1; ?>', $value);
        return $value;
    }

    /**
     * Handle Blade-like directives.
     */
    protected function compileDirectives(string $value): string
    {
        $patterns = [
            '/@extends\([\'"](.+?)[\'"]\)/'   => '<?php $__env->extend("$1"); ?>',
            '/@section\([\'"](.+?)[\'"]\)/'   => '<?php $__env->startSection("$1"); ?>',
            '/@endsection/'                   => '<?php $__env->endSection(); ?>',
            '/@yield\([\'"](.+?)[\'"]\)/'     => '<?php echo $__env->yieldContent("$1"); ?>',
            '/@include\([\'"](.+?)[\'"]\)/'   => '<?php echo $__env->make("$1"); ?>',
            '/@if\s*\((.+?)\)/'               => '<?php if($1): ?>',
            '/@elseif\s*\((.+?)\)/'           => '<?php elseif($1): ?>',
            '/@else/'                         => '<?php else: ?>',
            '/@endif/'                        => '<?php endif; ?>',
            '/@push\([\'"](.+?)[\'"]\)/'      => '<?php $__env->startPush("$1"); ?>',
            '/@endpush/'                      => '<?php $__env->endPush(); ?>',
            '/@stack\([\'"](.+?)[\'"]\)/'     => '<?php echo $__env->yieldPushContent("$1"); ?>',
            '/@once/'                         => '<?php if (! $__env->hasRenderedOnce()): $__env->markRenderedOnce(); ?>',
            '/@endonce/'                      => '<?php endif; ?>',
        ];

        $compiled = preg_replace(array_keys($patterns), array_values($patterns), $value);

        // Restore verbatim content
        if (!empty($this->verbatimBlocks)) {
            foreach ($this->verbatimBlocks as $key => $raw) {
                $compiled = str_replace($key, $raw, $compiled);
            }
        }

        return $compiled;
    }

    /**
     * Get compiled file path with hash and extension to prevent collisions.
     */
    protected function getCompiledPath(string $viewFile): string
    {
        $hash = md5(realpath($viewFile) ?: $viewFile);
        $extension = pathinfo($viewFile, PATHINFO_EXTENSION);
        return "{$this->cachePath}/{$hash}_{$extension}.php";
    }

    /**
     * Check if the compiled view is expired or should recompile.
     */
    protected function isExpired(string $viewFile, string $compiledFile): bool
    {
        if ($this->alwaysRecompile) {
            return true;
        }

        if (!file_exists($compiledFile)) {
            return true;
        }

        return filemtime($viewFile) > filemtime($compiledFile);
    }

    /**
     * Clear all compiled view cache files manually.
     */
    public function clearCache(): void
    {
        $files = glob($this->cachePath . '/*.php');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Auto-clean old cache files that no longer have a source view.
     */
    public function cleanStaleCache(): void
    {
        foreach (glob($this->cachePath . '/*.php') as $file) {
            $sourcePath = $this->extractSourceFromHash(basename($file));
            if ($sourcePath && !file_exists($sourcePath)) {
                unlink($file);
            }
        }
    }

    /**
     * Extract approximate source file from cache name (for cleanup).
     */
    protected function extractSourceFromHash(string $filename): ?string
    {
        $parts = explode('_', $filename);
        if (count($parts) < 2) {
            return null;
        }

        $extension = str_replace('.php', '', end($parts));
        foreach ($this->supportedExtensions as $ext) {
            if (str_contains($ext, $extension)) {
                return $ext;
            }
        }

        return null;
    }
}
