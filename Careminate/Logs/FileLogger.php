<?php declare(strict_types=1);

namespace Careminate\Logs;

class FileLogger implements LoggerInterface
{
    protected string $logFile;

    public function __construct(?string $path = null)
    {
        $logConfig = config('log', []);
        $channel = $logConfig['default'] ?? 'single';
        $channelConfig = $logConfig['channels'][$channel] ?? [];

        $this->logFile = $path 
            ?? $channelConfig['path'] 
            ?? storage_path('logs/app.log');

        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0775, true);
        }
    }

    public function log(string $message): void
    {
        $logEntry = sprintf(
            "[%s] %s%s",
            date('Y-m-d H:i:s'),
            $message,
            PHP_EOL
        );

        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}
