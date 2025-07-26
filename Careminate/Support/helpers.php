<?php declare(strict_types=1);

use Careminate\Http\Responses\Response;
use Careminate\Http\Responses\RedirectResponse;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        $lower = strtolower($value);
        return match (true) {
            $lower === 'true'  => true,
            $lower === 'false' => false,
            $lower === 'null'  => null,
            is_numeric($value) => $value + 0,
            default            => $value,
        };
    }
}

if (! function_exists('response')) {
    /**
     * Create a response instance.
     *
     * Usage examples:
     *  - response('Hello world')
     *  - response()->json([...])
     *  - response()->redirect('/login')
     *
     * @param string|null $content
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function response(string $content = '', int $status = Response::HTTP_OK, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (! function_exists('redirect')) {
    /**
     * Create a redirect response instance.
     *
     * Usage examples:
     *  - redirect('/home')
     *  - redirect('https://example.com')->setExitAfterRedirect(false)
     *
     * @param string $url
     * @param int $status
     * @return RedirectResponse
     */
    function redirect(string $url, int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }
}

if (!function_exists('debug_log')) {
    /**
     * Centralized debug logger or dumper.
     *
     * @param mixed $data
     * @param bool $toFile  Whether to log to file instead of outputting
     * @param string|null $label Optional label
     * @return void
     */
    function debug_log(mixed $data, bool $toFile = false, ?string $label = null): void
    {
        if (!env('APP_DEBUG', false)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $labelText = $label ? "[$label] " : '';

        if ($toFile) {
            $logPath = BASE_PATH . '/storage/logs/log.log';
            $output = $labelText . $timestamp . ' - ' . var_export($data, true) . PHP_EOL;
            file_put_contents($logPath, $output, FILE_APPEND);
        } else {
            echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ccc;color:#333'>";
            echo $labelText . $timestamp . "\n";
            print_r($data);
            echo "</pre>";
        }
    }
}

