<?php declare(strict_types=1);
namespace Careminate\Logs;

class Log extends \Exception 
{
    protected $log_file;
    
    public function __construct($message, $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        
        $logConfig = config('log', []);
        $channel = $logConfig['default'] ?? 'single';
        $channelConfig = $logConfig['channels'][$channel] ?? [];

        $this->log_file = $channelConfig['path'] ?? storage_path('logs/log.log');

        $this->logError();

        if ($logConfig['display_errors'] ?? false) {
            $this->displayError();
        }
    }

    public function logError()
    {
        $logMessage = date('Y-m-d H:i:s') . " - Error: [{$this->getCode()}] {$this->getMessage()} "
                    . "in {$this->getFile()} on line {$this->getLine()}\n";
        file_put_contents($this->log_file, $logMessage, FILE_APPEND);
    }

    public function displayError()
    {
        http_response_code(500);
        $message = htmlspecialchars($this->getMessage(), ENT_QUOTES);
        $line = $this->getLine();
        $file = $this->getFile();
        $trace = htmlspecialchars($this->getTraceAsString(), ENT_QUOTES);

        include base_path('resources/views/errors/exception.tpl.php');
        exit;
    }
}


