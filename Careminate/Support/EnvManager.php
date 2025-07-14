<?php declare(strict_types=1);

namespace Careminate\Support;

class EnvManager
{
    public static function basePath(): string
    {
        return defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
    }

    public static function envPath(): string
    {
        return self::basePath() . '/.env';
    }

    public static function examplePath(): string
    {
        return self::basePath() . '/.env.example';
    }

    public static function generateAppKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(64));
    }

    public static function readEnvFile(string $path): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: $path");
        }
        return file_get_contents($path);
    }

    public static function writeEnvFile(string $path, string $content): void
    {
        file_put_contents($path, $content);
    }

    public static function updateAppKey(string $key, bool $force = false): void
    {
        $envPath = self::envPath();
        $content = self::readEnvFile($envPath);

        if (preg_match('/^APP_KEY=.*$/m', $content)) {
            $content = preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", $content);
        } else {
            $content .= "\nAPP_KEY=$key\n";
        }

        self::writeEnvFile($envPath, $content);

        if ($force) {
            self::cleanupStorage();
        }
    }

    public static function validateAppKey(?string $key): void
    {
        if (!$key || trim($key) === '') {
            throw new \RuntimeException("Missing APP_KEY.");
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false || strlen($decoded) !== 64) {
                throw new \RuntimeException("APP_KEY must be a base64-encoded 64-byte string.");
            }
        } elseif (strlen($key) < 64) {
            throw new \RuntimeException("APP_KEY must be at least 64 characters if not base64.");
        }
    }

    public static function cleanupStorage(): void
    {
        $basePath = self::basePath();
        $paths = [
            'sessions' => "$basePath/storage/sessions",
            'cache'    => "$basePath/storage/cache",
            'views'    => "$basePath/storage/views",
            'logs'     => "$basePath/storage/logs",
        ];

        foreach ($paths as $label => $path) {
            if (!is_dir($path)) {
                echo "\nℹ️  Skipping $label: directory not found ($path)";
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

            echo "\n🧹 Cleared $deleted files from: storage/$label";
        }
    }
}
