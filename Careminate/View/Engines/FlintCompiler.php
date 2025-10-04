<?php declare(strict_types=1);

namespace Careminate\View\Engines;

class FlintCompiler
{
    protected string $viewPath;
    protected string $cachePath;
    protected FlintTemplateEngine $engine;

    public function __construct(string $viewPath, string $cachePath)
    {
        $this->viewPath = $viewPath;
        $this->cachePath = $cachePath;
        $this->engine = new FlintTemplateEngine();
        
        // Ensure cache directory exists
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
    }

    public function compile(string $template, array $data = []): string
    {
        // Convert dot notation to file path with .flint.php extension
        $templateFile = $this->resolveTemplatePath($template);
        $sourceFile = $this->viewPath . '/' . $templateFile;
        
        if (!file_exists($sourceFile)) {
            throw new \RuntimeException("View file not found: {$sourceFile} (resolved from: {$template})");
        }

        $cacheFile = $this->cachePath . '/' . md5($templateFile) . '.php';
        
        // Recompile if source is newer than cache
        if (!file_exists($cacheFile) || filemtime($sourceFile) > filemtime($cacheFile)) {
            $compiled = $this->compileFile($sourceFile);
            file_put_contents($cacheFile, $compiled);
        }

        return $cacheFile;
    }

    protected function resolveTemplatePath(string $template): string
    {
        // Convert dot notation to directory structure and add .flint.php extension
        return str_replace('.', '/', $template) . '.flint.php';
    }

    protected function compileFile(string $file): string
    {
        $content = file_get_contents($file);
        
        // Basic directive compilation
        $replacements = [
            // Layout and sections
            '/@extends\(\'([^\']+)\'\)/' => '<?php $__engine->extend(\'$1\'); ?>',
            '/@section\(\'([^\']+)\'\)/' => '<?php $__engine->startSection(\'$1\'); ?>',
            '/@endsection/' => '<?php $__engine->endSection(); ?>',
            '/@yield\(\'([^\']+)\',?\s*\'?([^\']*)\'?\)/' => '<?php echo $__engine->yield(\'$1\', \'$2\'); ?>',
            
            // Includes
            '/@include\(\'([^\']+)\'\)/' => '<?php echo $__engine->include(\'$1\'); ?>',
            
            // Stacks
            '/@push\(\'([^\']+)\'\)/' => '<?php $__engine->startPush(\'$1\'); ?>',
            '/@endpush/' => '<?php $__engine->endPush(); ?>',
            '/@stack\(\'([^\']+)\'\)/' => '<?php echo $__engine->yieldStack(\'$1\'); ?>',
            
            // Forms
            '/@csrf/' => '<?php echo $__engine->csrfField(); ?>',
            '/@method\(\'([^\']+)\'\)/' => '<?php echo $__engine->methodField(\'$1\'); ?>',
            
            // Assets
            '/@asset\(\'([^\']+)\'\)/' => '<?php echo $__engine->asset(\'$1\'); ?>',
            
            // Echo statements
            '/\{\{\s*(.+?)\s*\}\}/' => '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>',
            '/\{\!!\s*(.+?)\s*!!\}/' => '<?php echo $1; ?>',
            
            // Control structures
            '/@if\s*\((.+?)\)/' => '<?php if($1): ?>',
            '/@elseif\s*\((.+?)\)/' => '<?php elseif($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
            '/@foreach\s*\((.+?)\)/' => '<?php foreach($1): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            '/@for\s*\((.+?)\)/' => '<?php for($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',
            '/@while\s*\((.+?)\)/' => '<?php while($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',
        ];

        return preg_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function getEngine(): FlintTemplateEngine
    {
        return $this->engine;
    }
}


