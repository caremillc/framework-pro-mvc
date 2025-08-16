<?php declare(strict_types=1);

use Careminate\Http\Responses\Response;
use Careminate\Http\Responses\RedirectResponse;

// Just include the file at the top of your script
require_once 'debug.php';

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

// Env Function
if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     */
    function env(string $key, mixed $default = null): mixed
    {
        static $loaded = false;

        if (! $loaded && file_exists(BASE_PATH . '/.env')) {
            $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name           = trim($name);
                $value          = trim($value);

                if (! isset($_ENV[$name])) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
            $loaded = true;
        }

        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return value($default);
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default          => preg_match('/\A([\'"])(.*)\1\z/', $value, $m) ? $m[2] : $value,
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
    function redirect(string $url, int $status = Response::HTTP_FOUND,array $headers = []): RedirectResponse
    {
        return new RedirectResponse($url, $status,$headers);
    }
}

if (!function_exists('stream_json')) {
    function stream_json(iterable $data): Response
    {
        return Response::streamJson($data);
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

if (! function_exists('storage_path')) {
   function storage_path(string $path = ''): string
    {
        return BASE_PATH . '/storage' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('base_path')) {
    function base_path(?string $file = null): string
    {
        return rtrim(ROOT_PATH . '/../' . ($file ?? ''), '/');
    }
}

if (! function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $configs = [];

        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset($configs[$file])) {
            $path = base_path("config/{$file}.php");
            $configs[$file] = file_exists($path) ? require $path : [];
        }

        return \Careminate\Support\Arr::get($configs[$file], implode('.', $parts), $default);
    }
}