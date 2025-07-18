<?php declare(strict_types=1);

use Careminate\Encryption\Encrypter;
use Careminate\Http\Responses\Response;
use Careminate\Http\Responses\RedirectResponse;

// Just include the file at the top of your script
require_once 'debug.php';

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('base_path')) {
    function base_path(?string $file = null)
    {
        return ROOT_DIR . '/../' . $file;
    }
}

if (! function_exists('storage_path')) {
   function storage_path(string $path = ''): string
    {
        return BASE_PATH . '/storage' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('config')) {
    function config(?string $file = null)
    {
        $seprate = explode('.', $file);
        if ((! empty($seprate) && count($seprate) > 1) && ! is_null($file)) {
            $file = include base_path('config/') . $seprate[0] . '.php';
            return isset($file[$seprate[1]]) ? $file[$seprate[1]] : $file;
        }
        return $file;
    }
}

if (! function_exists('route_path')) {
    function route_path(?string $file = null)
    {
        return ! is_null($file) ? config('route.path') . '/' . $file : config('route.path');
    }
}

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

if (!function_exists('debug')) {
    /**
     * Centralized debug logger or dumper.
     *
     * @param mixed $data
     * @param bool $toFile  Whether to log to file instead of outputting
     * @param string|null $label Optional label
     * @return void
     */
    function debug(mixed $data, bool $toFile = false, ?string $label = null): void
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


// start env 
if (!function_exists('encrypter')) {
    function encrypter(): Encrypter
    {
        static $instance = null;

        if ($instance === null) {
            $key = env('APP_KEY');
            if (!$key) {
                throw new \RuntimeException("Missing APP_KEY in .env file.");
            }
            $instance = new Encrypter($key);
        }

        return $instance;
    }
}

if (!function_exists('encrypt')) {
    function encrypt(string $data): string
    {
        return encrypter()->encrypt($data);
    }
}

if (!function_exists('decrypt')) {
    function decrypt(string $payload): string
    {
        return encrypter()->decrypt($payload);
    }
}

// end env codes

// start load env
if (!function_exists('load_env')) {
    function load_env(): void
    {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) return;

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;

            [$name, $value] = array_pad(explode('=', $line, 2), 2, null);
            if ($name !== null) {
                $_ENV[trim($name)] = trim($value, "\"'");
            }
        }
    }
}

// end load env