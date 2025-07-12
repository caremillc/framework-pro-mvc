<?php declare(strict_types=1);

namespace Careminate\Console\Commands;

class EnvGenerateCommand
{
    public string $signature = 'env:generate';
    public string $description = 'Generate a .env file from .env.example';

    public function handle(): void
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $examplePath = $basePath . '/.env.example';
        $envPath = $basePath . '/.env';

        if (!file_exists($examplePath)) {
            echo "❌ .env.example file not found.\n";
            return;
        }

        if (file_exists($envPath)) {
            echo "⚠️  .env file already exists. Use --force to overwrite.\n";
            return;
        }

        $content = file_get_contents($examplePath);

        // ✅ Ensure APP_KEY line exists in the example
        if (!preg_match('/^APP_KEY=.*$/m', $content)) {
            $content .= "\nAPP_KEY=\n";
        }

        // ✅ Generate 64-byte base64 key
        $rawKey = random_bytes(64);
        $encodedKey = 'base64:' . base64_encode($rawKey);

        // ✅ Replace or insert APP_KEY
        $content = preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$encodedKey", $content);

        file_put_contents($envPath, $content);

        echo "✅ .env file created successfully with secure base64 APP_KEY.\n";
    }
}


