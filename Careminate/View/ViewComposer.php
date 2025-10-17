<?php declare(strict_types=1);
namespace Careminate\View;

class ViewComposer
{
    protected array $composers = [];
    protected array $shared = [];

    // Register a composer for a specific view or a wildcard (*)
    public function composer(string|array $views, callable $callback): void
    {
        foreach ((array) $views as $view) {
            $this->composers[$view][] = $callback;
        }
    }

    // Share data globally to all views
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    // Get shared data
    public function getShared(): array
    {
        return $this->shared;
    }

    // Apply composers to a view before rendering
    public function compose(string $view, array &$data): void
    {
        // Add global shared data
        $data = array_merge($this->shared, $data);

        // Apply wildcard composers first
        if (isset($this->composers['*'])) {
            foreach ($this->composers['*'] as $callback) {
                $callback($data);
            }
        }

        // Apply view-specific composers
        if (isset($this->composers[$view])) {
            foreach ($this->composers[$view] as $callback) {
                $callback($data);
            }
        }
    }
}
