<?php declare(strict_types=1);

namespace Careminate\View\Engines;

class FlintTemplateEngine
{
    protected array $sections = [];
    protected array $stacks = [];
    protected ?string $currentSection = null;
    protected array $layoutData = [];
    protected string $currentLayout = '';

    public function extend(string $layout): void
    {
        $this->currentLayout = $layout;
    }

    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    public function yield(string $section, string $default = ''): string
    {
        return $this->sections[$section] ?? $default;
    }

    public function include(string $view): string
    {
        // This would need to be handled by the compiler
        // For now, we'll return a placeholder
        return "<!-- Included: {$view} -->";
    }

    public function startPush(string $stack): void
    {
        if (!isset($this->stacks[$stack])) {
            $this->stacks[$stack] = [];
        }
        ob_start();
    }

    public function endPush(): void
    {
        if (!empty($this->stacks)) {
            $stack = array_key_last($this->stacks);
            $this->stacks[$stack][] = ob_get_clean();
        }
    }

    public function yieldStack(string $stack): string
    {
        if (isset($this->stacks[$stack])) {
            return implode('', $this->stacks[$stack]);
        }
        return '';
    }

    public function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . ($_SESSION['_token'] ?? '') . '">';
    }

    public function methodField(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . htmlspecialchars($method) . '">';
    }

    public function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }

    public function getLayout(): string
    {
        return $this->currentLayout;
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function reset(): void
    {
        $this->sections = [];
        $this->stacks = [];
        $this->currentSection = null;
        $this->currentLayout = '';
    }
}


