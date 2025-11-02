<?php declare(strict_types=1);
namespace Careminate\Support\Console\Traits;

trait CommandHelper
{
    protected int $verbosity = 1; // default normal output

    public function setVerbosity(int $level): void
    {
        $this->verbosity = $level;
    }

    protected function output(string $message, string $colorCode, string $symbol, int $minVerbosity = 1): void
    {
        if ($this->verbosity < $minVerbosity) {
            return;
        }
        fwrite(STDOUT, "\033[{$colorCode}m{$symbol} {$message}\033[0m" . PHP_EOL);
    }

    protected function info(string $message): void
    {
        $this->output($message, '32', '✔', 1);
    }

    protected function error(string $message): void
    {
        $this->output($message, '31', '✘', 0); // always show errors
    }

    protected function warning(string $message): void
    {
        $this->output($message, '33', '⚠', 1);
    }

    protected function confirm(string $message, bool $default = false): bool
    {
        if ($this->verbosity === 0) {
            return $default; // no prompt in silent mode
        }

        $yesNo = $default ? '[Y/n]' : '[y/N]';
        fwrite(STDOUT, "\033[33m? {$message} {$yesNo}: \033[0m");
        $response = strtolower(trim(fgets(STDIN)));

        if ($response === '') {
            return $default;
        }

        return in_array($response, ['y', 'yes'], true);
    }

    protected function choice(string $message, array $options, ?string $default = null): string
    {
        if ($this->verbosity === 0) {
            return $default ?? $options[0]; // no prompt in silent mode
        }

        $optionsStr = implode('/', $options);
        $defaultPrompt = $default ? " [{$default}]" : '';
        fwrite(STDOUT, "\033[36m? {$message} ({$optionsStr}){$defaultPrompt}: \033[0m");
        $response = trim(fgets(STDIN));

        if ($response === '' && $default !== null) {
            return $default;
        }

        while (!in_array($response, $options, true)) {
            fwrite(STDOUT, "\033[31mInvalid option. Please choose one of: {$optionsStr}\033[0m" . PHP_EOL);
            fwrite(STDOUT, "\033[36m? {$message} ({$optionsStr}){$defaultPrompt}: \033[0m");
            $response = trim(fgets(STDIN));
            if ($response === '' && $default !== null) {
                return $default;
            }
        }

        return $response;
    }

    /**
     * Display a progress bar
     * 
     * @param int $done Number of completed steps
     * @param int $total Total steps
     * @param string|null $message Optional message to display alongside
     * @param int $length Length of the progress bar (default 40)
     */
    protected function progress(int $done, int $total, ?string $message = null, int $length = 40): void
    {
        if ($this->verbosity < 1) {
            return; // no output in silent mode
        }

        $percent = $total > 0 ? ($done / $total) : 0;
        $filledLength = (int) round($length * $percent);

        $bar = str_repeat('=', $filledLength);
        if ($filledLength < $length) {
            $bar .= '>';
            $bar .= str_repeat(' ', $length - $filledLength - 1);
        }

        $percentDisplay = round($percent * 100);
        $msg = $message ? " {$message}" : '';

        // \r = return to line start, no newline so we overwrite
        fwrite(STDOUT, "\r[{$bar}] {$percentDisplay}%{$msg}");

        if ($done >= $total) {
            fwrite(STDOUT, PHP_EOL); // complete, move to next line
        }
    }
}
