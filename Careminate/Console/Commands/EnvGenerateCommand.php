<?php declare(strict_types=1);

namespace Careminate\Console\Commands;

use Careminate\Support\EnvManager;

class EnvGenerateCommand
{
    public string $signature = 'env:generate';
    public string $description = 'Generate a .env file from .env.example';

    public function handle(): void
    {
        try {
            $examplePath = EnvManager::examplePath();
            $envPath = EnvManager::envPath();

            if (!file_exists($examplePath)) {
                echo "❌ .env.example file not found.\n";
                return;
            }

            if (file_exists($envPath)) {
                echo "⚠️  .env file already exists. Use --force to overwrite.\n";
                return;
            }

            $content = EnvManager::readEnvFile($examplePath);
            $key = EnvManager::generateAppKey();
            $content = preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", $content);

            EnvManager::writeEnvFile($envPath, $content);

            echo "✅ .env file created successfully with secure base64 APP_KEY.\n";
        } catch (\Throwable $e) {
            echo "❌ Error: {$e->getMessage()}\n";
        }
    }
}
