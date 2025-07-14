<?php declare(strict_types=1);

namespace Careminate\Console\Commands;

use Careminate\Support\EnvManager;

class KeyGenerateCommand
{
    public string $signature = 'key:generate {--force}';
    public string $description = 'Generate a new base64-encoded APP_KEY and update the .env file';

    public function handle(array $options = []): void
    {
        try {
            if (!file_exists(EnvManager::envPath())) {
                echo "❌ .env file not found. Run `php caremi env:generate` first.\n";
                return;
            }

            if (!isset($options['--force']) && !$this->confirmRegeneration()) {
                echo "⚠️  Operation cancelled. APP_KEY was not changed.\n";
                return;
            }

            $key = EnvManager::generateAppKey();
            EnvManager::updateAppKey($key, isset($options['--force']));

            echo "✅ APP_KEY regenerated successfully.\n";
            echo "🔐 New key: $key\n";

            if (!isset($options['--force'])) {
                echo "⚠️  Warning: Sessions, cache, and compiled views may now be invalid.\n";
            }
        } catch (\Throwable $e) {
            echo "❌ Error: {$e->getMessage()}\n";
        }
    }

    private function confirmRegeneration(): bool
    {
        echo "⚠️  Regenerating APP_KEY will invalidate encrypted sessions, cache, views, etc.\n";
        echo "❓ Continue? [y/N]: ";
        $input = trim(fgets(STDIN));
        return strtolower($input) === 'y';
    }
}
