<?php declare(strict_types=1);
namespace Careminate\Logs;

use Careminate\Logs\Contracts\LoggerInterface;

class DailyFileLogger implements LoggerInterface
{
    protected string $logPath;

    public function __construct(?string $basePath = null)
    {
        $logConfig = config('log', []);
        $channelConfig = $logConfig['channels']['daily'] ?? [];

        $basePath = $basePath 
            ?? $channelConfig['path'] 
            ?? storage_path('logs/log.log');

        $dateFormat = $channelConfig['date_format'] ?? 'Y-m-d';
        $date = date($dateFormat);

        $ext = pathinfo($basePath, PATHINFO_EXTENSION);
        $dir = dirname($basePath);
        $name = pathinfo($basePath, PATHINFO_FILENAME);

        $this->logPath = "{$dir}/{$name}-{$date}." . ($ext ?: 'log');

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public function log(string $message): void
    {
        $entry = "[" . date('Y-m-d H:i:s') . "] {$message}" . PHP_EOL;
        file_put_contents($this->logPath, $entry, FILE_APPEND);
    }
}

