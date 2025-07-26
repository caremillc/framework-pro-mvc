<?php declare (strict_types = 1);
namespace Careminate\Support;

class Str
{
    public static function camel(string $value): string
    {
        return lcfirst(static::studly($value));
    }

    public static function studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value);
            $value = mb_strtolower($value, 'UTF-8');
        }
        return $value;
    }

    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public static function contains(string $haystack, string | array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function startsWith(string $haystack, string | array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function endsWith(string $haystack, string | array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        return mb_strwidth($value, 'UTF-8') <= $limit
        ? $value
        : rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function random(int $length = 16): string
    {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }

    public static function slug(string $title, string $separator = '-'): string
    {
        $title = static::ascii($title);
        // Remove unwanted characters and convert to lowercase
        $title = preg_replace('~[^\pL\d]+~u', $separator, $title);
        $title = preg_replace('~[' . preg_quote($separator) . ']+~', $separator, $title);
        return trim(mb_strtolower($title), $separator);
    }

    public static function ascii(string $value): string
    {
        // Basic ASCII conversion; can be extended for UTF-8 mappings
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    }
}



