<?php declare(strict_types=1);

use Careminate\Support\Collection;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Http\Responses\RedirectResponse;

/**
 * ================================
 * Start Request Class
 * ================================ 
 * */
if (!function_exists('value')) {
    /**
     * Return the default value of a variable or call it if Closure
     */
    function value(mixed $value): mixed
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}
if (!function_exists('request')) {
    /**
     * Get the current Request instance or a specific input value.
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function request(string|array|null $key = null, mixed $default = null): mixed
    {
        static $instance = null;

        if ($instance === null) {
            $instance = Request::createFromGlobals();
        }

        if (is_string($key)) {
            return $instance->input($key, $default);
        }

        if (is_array($key)) {
            return $instance->only($key);
        }

        return $instance;
    }
}

/**
 * Shortcut: Get only specified input keys.
 *
 * @param array|string ...$keys
 * @return array
 */
if (!function_exists('request_only')) {
    function request_only(array|string ...$keys): array
    {
        return request()->only(...$keys);
    }
}

/**
 * Shortcut: Get all input except specified keys.
 *
 * @param array|string ...$keys
 * @return array
 */
if (!function_exists('request_except')) {
    function request_except(array|string ...$keys): array
    {
        return request()->except(...$keys);
    }
}

/**
 * Shortcut: Get all input data (GET + POST + JSON + raw input merged)
 *
 * @return array
 */
if (!function_exists('request_all')) {
    function request_all(): array
    {
        return request()->all();
    }
}

/**
 * Shortcut: Get JSON payload as array.
 *
 * @return array
 */
if (!function_exists('request_json')) {
    function request_json(): array
    {
        return request()->json();
    }
}

/**
 * Shortcut: Check if a key exists in input.
 *
 * @param string $key
 * @return bool
 */
if (!function_exists('request_has')) {
    function request_has(string $key): bool
    {
        return request()->has($key);
    }
}

/**
 * Shortcut: Get a cookie value.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('request_cookie')) {
    function request_cookie(string $key, mixed $default = null): mixed
    {
        return request()->cookie($key, $default);
    }
}

/**
 * Shortcut: Get a header value.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('request_header')) {
    function request_header(string $key, mixed $default = null): mixed
    {
        return request()->header($key) ?? $default;
    }
}

if (!function_exists('data_get')) {
    /**
     * Get a value from an array or object using dot notation
     */
    function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) return $target;

        $key = (string)$key;
        if (is_array($target) && array_key_exists($key, $target)) {
            return $target[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('data_set')) {
    /**
     * Set a value in an array or object using dot notation
     */
    function data_set(mixed &$target, string|int $key, mixed $value): void
    {
        $keys = explode('.', (string)$key);

        while (count($keys) > 1) {
            $segment = array_shift($keys);

            if (is_array($target)) {
                if (!isset($target[$segment]) || !is_array($target[$segment])) {
                    $target[$segment] = [];
                }
                $target = &$target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment}) || !is_object($target->{$segment})) {
                    $target->{$segment} = new \stdClass();
                }
                $target = &$target->{$segment};
            } else {
                throw new \RuntimeException("Cannot set key on non-array/object.");
            }
        }

        $last = array_shift($keys);
        if (is_array($target)) $target[$last] = $value;
        elseif (is_object($target)) $target->{$last} = $value;
    }
}


/**
 * ================================
 * End Request Class
 * ================================ 
 * */

/**
 * ================================
 * Start Collection Class
 * ================================ 
 * */
if (!function_exists('collect')) {
    function collect(mixed $items = []): Collection
    {
        if (!is_array($items)) $items = [$items];
        return new Collection($items);
    }
}

/**
 * ================================
 * End Collection Class
 * ================================ 
 * */

/**
 * Start
 * Response and ResponseRedirection
 */

if (!function_exists('env')) {
    /**
     * Get an environment variable, or return the default value if not found.
     *
     * Supports various data types.
     *
     * @param string $key The name of the environment variable.
     * @param mixed $default The default value to return if the environment variable is not found.
     * @return mixed The value of the environment variable or the default value.
     */
    function env(string $key, $default = null)
    {
        // check superglobals first, then getenv() reliably
        if (array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        } else {
            $g = getenv($key);
            $value = ($g !== false) ? $g : $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmedValue = trim($value);

        return match (strtolower($trimmedValue)) {
            'true' => true,
            'false' => false,
            'null' => null,
            'empty' => '',
            default => is_numeric($trimmedValue) ? (str_contains($trimmedValue, '.') ? (float)$trimmedValue : (int)$trimmedValue) : (
                preg_match('/^[\[{].*[\]}]$/', $trimmedValue) ? (json_decode($trimmedValue, true) ?? $trimmedValue) : $trimmedValue
            )
        };
    }
}

if (! function_exists('public_path')) {
    function public_path(?string $file = null): string
    {
        return base_path('public' . ($file ? '/' . $file : ''));
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (! function_exists('app_path')) {
    function app_path(?string $file = null): string
    {
        return base_path('app' . ($file ? '/' . $file : ''));
    }
}

if (! function_exists('config_path')) {
    function config_path(?string $file = null): string
    {
        return base_path('config' . ($file ? '/' . $file : ''));
    }
}

if (! function_exists('storage_path')) {
    function storage_path(?string $file = null): string
    {
        return base_path('storage' . ($file ? '/' . $file : ''));
    }
}

if (! function_exists('resource_path')) {
    function resource_path(?string $file = null): string
    {
        return base_path('resources' . ($file ? '/' . $file : ''));
    }
}

if (!function_exists('route_path')) {
    function route_path(string $path = ''): string
    {
        return base_path('routes' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        static $config = null;
        
        if ($config === null) {
            $configPath = base_path('config');
            $config = [];
            
            foreach (glob($configPath . '/*.php') as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $config[$name] = require $file;
            }
        }
        
        return array_get($config, $key, $default);
    }
}

if (!function_exists('array_get')) {
    function array_get(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
}

if (!function_exists('response')) {
    /**
     * Create a basic response.
     */
    function response(
        string $content = '',
        int $status = Response::HTTP_OK,
        array $headers = []
    ): Response {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('json')) {
    /**
     * Create a JSON response.
     */
    function json(
        mixed $data,
        int $status = Response::HTTP_OK,
        array $headers = []
    ): Response {
        return Response::json($data, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response.
     */
    function redirect(string $url, int $status = RedirectResponse::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }
}

if (!function_exists('download')) {
    /**
     * Create a file download response.
     */
    function download(string $filePath, ?string $fileName = null): Response
    {
        return Response::download($filePath, $fileName);
    }
}

if (!function_exists('abort')) {
    /**
     * Abort request with an error response.
     */
    function abort(int $status, string $message = ''): void
    {
        $response = match ($status) {
            400 => Response::badRequest($message ?: 'Bad Request'),
            401 => Response::unauthorized($message ?: 'Unauthorized'),
            403 => Response::forbidden($message ?: 'Forbidden'),
            404 => Response::notFound($message ?: 'Not Found'),
            500 => Response::serverError($message ?: 'Server Error'),
            default => new Response($message, $status),
        };

        $response->send();
        exit;
    }
}

if (!function_exists('back')) {
    /**
     * Redirect back to the previous URL.
     */
    function back(): RedirectResponse
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return new RedirectResponse($referer);
    }
}


/**
 * End
 * Response and ResponseRedirection
 */


// if (!function_exists('config')) {
//     function config(string $key, $default = null) {
//         return Config::get($key, $default);
//     }
// }

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('app')) {
    function app(?string $key = null) {
        global $app;
        return $key ? $app->make($key) : $app;
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): ?string {
        global $kernel;
        if (isset($kernel) && method_exists($kernel, 'router')) {
            return $kernel->router()->routeUrl($name, $params);
        }
        return null;
    }
}

// if (!function_exists('route')) {
//     /**
//      * Generate a URL from a named route.
//      *
//      * @param string $name   The name of the route
//      * @param array  $params Parameters to replace {placeholders} or append as query
//      * @return string
//      */
//     function route(string $name, array $params = []): string
//     {
//         global $kernel;

//         if (!isset($kernel) || !method_exists($kernel, 'router')) {
//             return '/'; // Fallback if kernel is not initialized
//         }

//         $router = $kernel->router();

//         // Separate query params from route placeholders
//         $routeParams = [];
//         $queryParams = [];

//         foreach ($params as $key => $value) {
//             // Placeholder keys look like {id} in the URI pattern
//             if (strpos($router->routeUrl($name, [$key => $value]), '{') === false) {
//                 $routeParams[$key] = $value;
//             } else {
//                 $queryParams[$key] = $value;
//             }
//         }

//         $url = $router->routeUrl($name, $routeParams);

//         if (!$url) {
//             return '/'; // fallback if route name not found
//         }

//         // Append query string if provided
//         if (!empty($queryParams)) {
//             $url .= '?' . http_build_query($queryParams);
//         }

//         return $url;
//     }
// }


/**
 * View 
 */
// if (!function_exists('base_path')) {
//     function base_path(string $path = ''): string
//     {
//         return rtrim(BASE_PATH . '/' . ltrim($path, '/'), '/');
//     }
// }

if (!function_exists('view_path')) {
    function view_path(string $path = ''): string
    {
        return base_path('resources/views/' . ltrim($path, '/'));
    }
}

// if (!function_exists('view')) {
//     function view(string $template, array $data = []): string
//     {
//         global $kernel;

//         if (!isset($kernel)) {
//             throw new \RuntimeException("View engine not initialized.");
//         }

//         $engine = $kernel->viewEngine();
//         return $engine->render($template, $data);
//     }
// }
// if (!function_exists('view')) {
//     function view(string $name, array $data = []): string
//     {
//         global $kernel;

//         if (isset($kernel) && method_exists($kernel, 'view')) {
//             return $kernel->view()->render($name, $data);
//         }

//         throw new RuntimeException('View system not initialized.');
//     }
// }

if (!function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        global $kernel;

        if (!isset($kernel)) {
            throw new \RuntimeException("Kernel not initialized for view rendering.");
        }

        return $kernel->view()->render($name, $data);
    }
}
/**
 * End View
 */