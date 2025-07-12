<?php declare(strict_types=1);

namespace Careminate\Console\Commands;

class EnvValidateCommand
{
    public string $signature = 'env:validate';
    public string $description = 'Validate required .env variables';

    /**
     * List of required environment variables
     */
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

            // Additional check for APP_KEY format
            if ($key === 'APP_KEY') {
                if (str_starts_with($value, 'base64:')) {
                    $decoded = base64_decode(substr($value, 7), true);
                    if ($decoded === false || strlen($decoded) !== 64) {
                        $missing[] = 'APP_KEY must be a base64-encoded 64-byte string';
                    }
                } elseif (strlen($value) < 64) {
                    $missing[] = 'APP_KEY must be at least 64 characters if not base64';
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


