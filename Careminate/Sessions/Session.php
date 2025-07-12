<?php declare(strict_types=1);
namespace Careminate\Sessions;

class Session
{
    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = encrypt(serialize($value));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! isset($_SESSION[$key])) {
            return $default;
        }

        try {
            return unserialize(decrypt($_SESSION[$key]));
        } catch (\Throwable $e) {
            return $default;
        }
    }

}


