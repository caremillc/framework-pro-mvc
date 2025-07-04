<?php declare(strict_types=1);
namespace Careminate\Console\Commands;

class EncryptCommand
{
    public string $signature = 'encrypt {data}';
    public string $description = 'Encrypt a string using APP_KEY';

    public function handle(array $args): void
    {
        $data = $args['data'] ?? null;
        if (!$data) {
            echo "❌ Please provide a string to encrypt.\n";
            return;
        }

        echo "🔐 Encrypted: " . encrypt($data) . "\n";
    }
}
