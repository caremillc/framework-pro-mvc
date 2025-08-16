<?php declare(strict_types=1);

namespace Careminate\Support;

final class EnvLoader
{
    /**
     * Load the .env file into $_ENV / $_SERVER.
     */
    public static function load(string $path): void
    {
        $envFile = rtrim($path, DIRECTORY_SEPARATOR) . '/.env';

        if (! file_exists($envFile)) {
            throw new \RuntimeException('Environment file (.env) is missing');
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Strip surrounding quotes
            $value = preg_replace('/^([\"\'])(.*)\\1$/', '$2', $value);

            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}
