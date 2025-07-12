<?php declare(strict_types=1);

namespace Careminate\Console\Commands;

class KeyGenerateCommand
{
    public string $signature = 'key:generate {--force}';
    public string $description = 'Generate a new base64-encoded APP_KEY and update the .env file';

    public function handle(array $options = []): void
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $envPath = $basePath . '/.env';

        if (!file_exists($envPath)) {
            echo "❌ .env file not found. Run `php caremi env:generate` first.\n";
            return;
        }

        // Confirm if --force is not passed
        if (!isset($options['--force']) && !$this->confirmRegeneration()) {
            echo "⚠️  Operation cancelled. APP_KEY was not changed.\n";
            return;
        }

        // Generate a new 64-byte secure base64 key
        $key = 'base64:' . base64_encode(random_bytes(64));

        $content = file_get_contents($envPath);

        if (preg_match('/^APP_KEY=.*$/m', $content)) {
            $content = preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", $content);
        } else {
            $content .= "\nAPP_KEY=$key\n";
        }

        file_put_contents($envPath, $content);

        echo "✅ APP_KEY regenerated successfully.\n";
        echo "🔐 New key: $key\n";

        if (isset($options['--force'])) {
            $this->cleanupStorage($basePath);
        } else {
            echo "⚠️  Warning: Sessions, cache, and compiled views may now be invalid.\n";
        }
    }

    private function confirmRegeneration(): bool
    {
        echo "⚠️  Regenerating APP_KEY will invalidate encrypted sessions, cache, views, etc.\n";
        echo "❓ Continue? [y/N]: ";
        $input = trim(fgets(STDIN));
        return strtolower($input) === 'y';
    }

    private function cleanupStorage(string $basePath): void
    {
        $paths = [
            'sessions' => "$basePath/storage/sessions",
            'cache'    => "$basePath/storage/cache",
            'views'    => "$basePath/storage/views",
            'logs'     => "$basePath/storage/logs",
        ];

        foreach ($paths as $label => $path) {
            if (!is_dir($path)) {
                echo "ℹ️  Skipping $label: directory not found ($path)\n";
                continue;
            }

            $files = glob("$path/*");
            $deleted = 0;

            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $deleted++;
                }
            }

            echo "🧹 Cleared $deleted files from: storage/$label\n";
        }
    }
}


