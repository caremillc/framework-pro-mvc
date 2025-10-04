<?php declare(strict_types=1);

namespace Careminate\Session;

class Session
{
    protected const FLASH_KEY = '_flash';
    protected const FLASH_NEW = '_flash_new';
    protected const FLASH_OLD = '_flash_old';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[self::FLASH_NEW] ??= [];
        $_SESSION[self::FLASH_OLD] ??= [];
        $_SESSION[self::FLASH_KEY] ??= [];

        // Remove flash data marked as "old" from the previous request
        foreach ($_SESSION[self::FLASH_OLD] as $key) {
            unset($_SESSION[self::FLASH_KEY][$key]);
        }

        // Move current "new" to "old"
        $_SESSION[self::FLASH_OLD] = $_SESSION[self::FLASH_NEW];
        $_SESSION[self::FLASH_NEW] = [];
    }

    /**
     * Set a flash message (available for next request)
     */
    public function flash(string $key, string $value): void
    {
        $_SESSION[self::FLASH_KEY][$key] = $value;
        $_SESSION[self::FLASH_NEW][] = $key;
    }

    /**
     * Retrieve (without deleting yet)
     */
    public function getFlash(string $key): ?string
    {
        return $_SESSION[self::FLASH_KEY][$key] ?? null;
    }

    /**
     * Old-style shortcut to fetch and forget immediately
     */
    public function consumeFlash(string $key): ?string
    {
        $value = $_SESSION[self::FLASH_KEY][$key] ?? null;
        if ($value !== null) {
            unset($_SESSION[self::FLASH_KEY][$key]);
        }
        return $value;
    }

    public function allFlashes(): array
    {
        return $_SESSION[self::FLASH_KEY];
    }

    public function clearFlashes(): void
    {
        $_SESSION[self::FLASH_KEY] = [];
        $_SESSION[self::FLASH_NEW] = [];
        $_SESSION[self::FLASH_OLD] = [];
    }

    // Normal session helpers
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        return $_SESSION;
    }
}
