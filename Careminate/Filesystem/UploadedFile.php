<?php declare (strict_types = 1);
namespace Careminate\Filesystem;

use Careminate\Validation\Validate;

class UploadedFile
{
    protected string $originalName;
    protected string $mimeType;
    protected string $tmpName;
    protected int $size;
    protected int $error;

    public function __construct(array $file)
    {

        if (! isset($file['tmp_name']) || ! is_string($file['tmp_name'])) {
            throw new \InvalidArgumentException("Invalid uploaded file array.");
        }

        $this->originalName = $file['name'] ?? '';
        $this->mimeType     = $file['type'] ?? '';
        $this->tmpName      = $file['tmp_name'];
        $this->size         = $file['size'] ?? 0;
        $this->error        = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    }

    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    public function getClientMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpName);
    }

    public function move(string $directory, ?string $name = null): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        $filename = $name ?? basename($this->originalName);
        $path     = rtrim($directory, '/') . '/' . $filename;

        return move_uploaded_file($this->tmpName, $path);
    }

    public function store(string $directory, ?string $filename = null): ?string
    {
        $filename = $filename ?? uniqid('', true) . '.' . pathinfo($this->originalName, PATHINFO_EXTENSION);

        if ($this->move($directory, $filename)) {
            return rtrim($directory, '/') . '/' . $filename;
        }

        return null;
    }

    public function isMime(string | array $mime): bool
    {
        $mimeTypes = (array) $mime;
        return in_array($this->mimeType, $mimeTypes, true);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    public function hasExtension(array $allowed): bool
    {
        return in_array($this->getExtension(), $allowed, true);
    }

    public function getExtension(): string
    {
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }

    public function storeAs(string $path, string $name, bool $fullPath = true): string | false
    {
        $destination = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        if (move_uploaded_file($this->tmpName, $destination)) {
            return $fullPath ? $destination : $name;
        }

        return false;
    }

    public function validate(array $rules): bool
    {
        $validator = new Validate(
            ['file' => $this],
            ['file' => $rules]
        );

        return $validator->passes();
    }

    public static function file(string $name): ?UploadedFile
    {
        if (!isset($_FILES[$name])) {
            return null;
        }
        return new UploadedFile($_FILES[$name]);
    }

}
