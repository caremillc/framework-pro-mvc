<?php declare(strict_types=1);

namespace Careminate\Console\Commands;

use Careminate\Support\EnvManager;

class EnvValidateCommand
{
    public string $signature = 'env:validate';
    public string $description = 'Validate required .env variables';

    protected array $required = [
        'APP_NAME',
        'APP_ENV',
        'APP_DEBUG',
        'APP_KEY',
    ];

    public function handle(): void
    {
        $missing = [];

        foreach ($this->required as $key) {
            $value = env($key);

            if ($value === null || $value === '') {
                $missing[] = "$key is missing or empty";
                continue;
            }

            if ($key === 'APP_KEY') {
                try {
                    EnvManager::validateAppKey($value);
                } catch (\Throwable $e) {
                    $missing[] = $e->getMessage();
                }
            }
        }

        if (!empty($missing)) {
            echo "❌ Environment validation failed:\n";
            foreach ($missing as $error) {
                echo "  - $error\n";
            }
            exit(1);
        }

        echo "✅ All required environment variables are set and valid.\n";
    }
}
