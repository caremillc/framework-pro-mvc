<?php declare(strict_types=1);
namespace Careminate\Session;

class Session implements SessionInterface
{
    private const FLASH_KEY = 'flash';
    public const AUTH_KEY = 'auth_id';

      public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Ensure CSRF token exists, else generate it
        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32))); // set csrf_token in the session
        }

        // dd($_SESSION['csrf_token']);
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function getFlash(string $type): array
    {
        $flash = $this->get(self::FLASH_KEY) ?? [];
        if (isset($flash[$type])) {
            $messages = $flash[$type];
            unset($flash[$type]);
            $this->set(self::FLASH_KEY, $flash);
            return $messages;
        }

        return [];
    }

    public function setFlash(string $type, string $message): void
    {
        $flash = $this->get(self::FLASH_KEY) ?? [];
        $flash[$type][] = $message;
        $this->set(self::FLASH_KEY, $flash);
    }

    public function hasFlash(string $type): bool
    {
        return isset($_SESSION[self::FLASH_KEY][$type]);
    }

    public function clearFlash(): void
    {
        unset($_SESSION[self::FLASH_KEY]);
    }

     public function auth(): bool
    {
        return $this->has(self::AUTH_KEY);
    }
}