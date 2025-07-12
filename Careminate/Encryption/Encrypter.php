<?php declare(strict_types=1);
namespace Careminate\Encryption;

use RuntimeException;

class Encrypter
{
    private string $key;

  public function __construct(string $key)
    {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);

            if ($key === false || strlen($key) !== 64) {
                throw new \RuntimeException("Invalid base64 APP_KEY. Must decode to exactly 64 bytes.");
            }
        } else {
            if (strlen($key) < 64) {
                throw new \RuntimeException("APP_KEY must be at least 64 characters long.");
            }
        }

        $this->key = hash('sha256', $key, true); // Still secure with SHA-256 hash
    }

    public function encrypt(string $data): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $payload): string
    {
        $data = base64_decode($payload);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $this->key, 0, $iv);
    }
}
