<?php declare(strict_types=1);

namespace Careminate\View\ViewEngines;

/**
 * ViewEngine
 * -----------
 * Manages rendering, layouts, sections, stacks, and "once" directives.
 */
class ViewEngine
{
    protected BladeLikeCompiler $compiler;
    protected array $sections = [];
    protected array $sectionStack = [];
    protected ?string $layout = null;

    // 🔹 Added for @push/@stack/@once directives
    protected array $pushStacks = [];
    protected bool $hasRenderedOnce = false;

    public function __construct(BladeLikeCompiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Render a view and return the compiled output.
     */
    public function render(string $view, array $data = []): string
    {
        $__env = $this; // make available inside templates
        extract($data, EXTR_SKIP);

        $compiledFile = $this->compiler->compile($view);

        ob_start();
        include $compiledFile;
        $content = ob_get_clean();

        if ($this->layout) {
            $layout = $this->layout;
            $this->layout = null;
            return $this->render($layout, array_merge($data, ['content' => $content]));
        }

        return $content;
    }

    // 🔹 Layout inheritance
    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    // 🔹 Section management (@section / @endsection / @yield)
    public function startSection(string $section): void
    {
        $this->sectionStack[] = $section;
        ob_start();
    }

    public function endSection(): void
    {
        $section = array_pop($this->sectionStack);
        $this->sections[$section] = ob_get_clean();
    }

    public function yieldContent(string $section): string
    {
        return $this->sections[$section] ?? '';
    }

    // 🔹 Include partial views
    public function make(string $view, array $data = []): string
    {
        return $this->render($view, $data);
    }

    // 🔹 Stack Management (@push / @stack)
    public function startPush(string $stack): void
    {
        ob_start();
        $this->sectionStack[] = $stack;
    }

    public function endPush(): void
    {
        $stack = array_pop($this->sectionStack);
        $this->pushStacks[$stack][] = ob_get_clean();
    }

    public function yieldPushContent(string $stack): string
    {
        return isset($this->pushStacks[$stack])
            ? implode("\n", $this->pushStacks[$stack])
            : '';
    }

    // 🔹 Once directive (@once / @endonce)
    public function hasRenderedOnce(): bool
    {
        return $this->hasRenderedOnce;
    }

    public function markRenderedOnce(): void
    {
        $this->hasRenderedOnce = true;
    }
}
