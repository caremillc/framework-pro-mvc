<?php declare(strict_types=1);

namespace Careminate\Providers;

use Dotenv\Dotenv;

class EnvironmentServiceProvider
{
    public function register(): void
    {
        $dotenv = Dotenv::createImmutable(BASE_PATH);
        $dotenv->safeLoad();

        // Validate required environment variables
        $required =  ['APP_NAME', 'APP_ENV', 'APP_KEY','APP_DEBUG'];
        foreach ($required as $key) {
            if (!isset($_ENV[$key]) || trim($_ENV[$key]) === '') {
                throw new \RuntimeException("Missing required environment key: {$key}");
            }
        }
    }
}

