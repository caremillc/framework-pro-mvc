<?php declare(strict_types=1);

namespace Careminate\Console;

class CommandDispatcher
{
    protected array $commands = [];

    public function __construct()
    {
        $this->loadCommandsFromConfig();
    }

    public function dispatch(array $argv): void
    {
        $script = array_shift($argv); // caremi

        $commandName = $argv[0] ?? null;

        if (!$commandName) {
            $this->listAvailableCommands();
            return;
        }

        $args = $this->parseArguments(array_slice($argv, 1));

        if (!isset($this->commands[$commandName])) {
            echo "❌ Unknown command: $commandName\n";
            $this->listAvailableCommands();
            exit(1);
        }

        $class = $this->commands[$commandName];
        $instance = new $class();

        if (!method_exists($instance, 'handle')) {
            echo "❌ Command [$commandName] must have a handle() method.\n";
            exit(1);
        }

        echo "⚙️  Running: $commandName\n";

        $reflection = new \ReflectionMethod($instance, 'handle');
        $paramCount = $reflection->getNumberOfParameters();

        // If the method expects args, pass them in
        if ($paramCount > 0) {
            $instance->handle($args);
        } else {
            $instance->handle();
        }
    }

    protected function loadCommandsFromConfig(): void
    {
        $path = BASE_PATH . '/config/console.php';

        if (!file_exists($path)) {
            echo "❌ config/console.php not found.\n";
            exit(1);
        }

        $this->commands = require $path;
    }

    protected function listAvailableCommands(): void
    {
        echo "🧭 Available commands:\n";

        foreach ($this->commands as $signature => $class) {
            $desc = property_exists($class, 'description') ? (new $class())->description : '';
            echo "  - $signature";
            echo $desc ? " → $desc" : '';
            echo "\n";
        }

        echo "\nℹ️  Usage: php caremi <command> [args]\n";
    }

    protected function parseArguments(array $argv): array
    {
        $args = [];

        foreach ($argv as $index => $arg) {
            if (str_contains($arg, '=')) {
                [$key, $value] = explode('=', $arg, 2);
                $args[$key] = $value;
            } else {
                $args[$index] = $arg;
            }
        }

        return $args;
    }
}
