<?php declare(strict_types=1);
namespace Careminate\Filesystem;

class FileRequest
{
    public static function file(string $name): ?UploadedFile
    {
        return isset($_FILES[$name]) ? new UploadedFile($_FILES[$name]) : null;
    }

    public static function hasFile(string $name): bool
    {
        return isset($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK;
    }

    public static function all(): array
    {
        $files = [];

        foreach ($_FILES as $key => $value) {
            $files[$key] = new UploadedFile($value);
        }

        return $files;
    }

    public static function getOriginalName(string $name): ?string
    {
        return $_FILES[$name]['name'] ?? null;
    }

    public static function getMimeType(string $name): ?string
    {
        return $_FILES[$name]['type'] ?? null;
    }

    public static function getSize(string $name): ?int
    {
        return $_FILES[$name]['size'] ?? null;
    }

    public static function move(string $name, string $destination, ?string $filename = null): bool
    {
        $file = self::file($name);

        return $file ? $file->move($destination, $filename) : false;
    }

    public static function store(string $name, string $to, ?string $filename = null): string|false
    {
        $file = self::file($name);

        return $file ? $file->store($to, $filename) : false;
    }
}
