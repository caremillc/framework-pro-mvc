<?php declare(strict_types=1);

use Careminate\Http\Responses\Response;
use Careminate\Http\Responses\RedirectResponse;

// if (!function_exists('env')) {
//     function env(string $key, mixed $default = null): mixed
//     {
//         $value = $_ENV[$key] ?? getenv($key);

//         if ($value === false) {
//             return $default;
//         }

//         $lower = strtolower($value);
//         return match (true) {
//             $lower === 'true'  => true,
//             $lower === 'false' => false,
//             $lower === 'null'  => null,
//             is_numeric($value) => $value + 0,
//             default            => $value,
//         };
//     }
// }

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable with type conversion.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        // Handle quoted strings
        if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
            return $matches[2];
        }

        return $value;
    }
}


if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
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
    function redirect(string $url, int $status = Response::HTTP_FOUND,array $headers = []): RedirectResponse
    {
        return new RedirectResponse($url, $status,$headers);
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

if (!function_exists('stream_json')) {
    function stream_json(iterable $data): Response
    {
        return Response::streamJson($data);
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = '', array $headers = []): never
    {
        $response = new Response($message, $code, $headers);
        $response->send();
        exit;
    }
}

if (!function_exists('json_serializer')) {
    function json_serializer(callable $serializer): void
    {
        Response::setJsonSerializer($serializer);
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = '', array $headers = []): never
    {
        $response = response($message, $code, $headers);
        $response->send();
        exit;
    }
}

if (!function_exists('logger')) {
    function logger(string $message, array $context = [], string $level = 'info'): void
    {
        $logPath = BASE_PATH . '/storage/logs/log.log';
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        
        $logMessage = sprintf(
            "[%s] %s: %s %s%s",
            $timestamp,
            $level,
            $message,
            json_encode($context),
            PHP_EOL
        );
        
        file_put_contents($logPath, $logMessage, FILE_APPEND);
    }
}








