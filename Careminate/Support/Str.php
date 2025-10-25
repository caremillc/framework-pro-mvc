<?php declare(strict_types=1);

namespace Careminate\Support;

use ArrayAccess;
use IteratorAggregate;
use Countable;
use Traversable;
use Closure;
use BadMethodCallException;


/**
 * ================================
 * STRING UTILITIES
 * ================================
 */
class Str
{
    public static function camel(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value))));
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', $delimiter.'$0', $value)), $delimiter);
    }

    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    public static function title(string $value): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $value));
    }

    public static function lower(string $value): string
    {
        return mb_strtolower($value);
    }

    public static function upper(string $value): string
    {
        return mb_strtoupper($value);
    }

    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit) . $end;
    }

    public static function contains(string $haystack, string|array $needles): bool
    {
        foreach ((array)$needles as $needle) if ($needle !== '' && str_contains($haystack, $needle)) return true;
        return false;
    }

    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array)$needles as $needle) if ($needle !== '' && str_starts_with($haystack, $needle)) return true;
        return false;
    }

    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array)$needles as $needle) if ($needle !== '' && str_ends_with($haystack, $needle)) return true;
        return false;
    }

    public static function after(string $subject, string $search): string
    {
        if ($search === '') return $subject;
        $pos = strpos($subject, $search);
        return $pos === false ? '' : substr($subject, $pos + strlen($search));
    }

    public static function before(string $subject, string $search): string
    {
        if ($search === '') return $subject;
        $pos = strpos($subject, $search);
        return $pos === false ? '' : substr($subject, 0, $pos);
    }

    public static function random(int $length = 16): string
    {
        return substr(bin2hex(random_bytes((int)ceil($length / 2))), 0, $length);
    }

    public static function slug(string $title, string $separator = '-'): string
    {
        $title = preg_replace('/[^\pL\pN]+/u', $separator, $title);
        $title = trim($title, $separator);
        return mb_strtolower($title);
    }

    public static function slugify(string $text, array $opts = []): string
    {
        $options = array_merge([
            'separator' => '-',
            'limit' => null,
            'lowercase' => true,
            'transliterate' => true,
            'ascii' => true,
            'locale' => null,
        ], $opts);

        $sep = $options['separator'];
        $limit = $options['limit'];
        $lowercase = $options['lowercase'];
        $transliterate = $options['transliterate'];
        $asciiOnly = $options['ascii'];
        $locale = $options['locale'];

        if (extension_loaded('intl') && class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_KD);
            if ($normalized !== false) $text = $normalized;
        }

        if ($transliterate) {
            if (function_exists('transliterator_transliterate')) {
                $try = @transliterator_transliterate('Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove;', $text);
                if ($try !== false && $try !== null) $text = $try;
            } elseif (function_exists('iconv')) {
                $try = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
                if ($try !== false && $try !== null) $text = $try;
            }
        }

        $text = preg_replace('/\p{M}/u', '', $text) ?? $text;

        if ($asciiOnly) {
            $text = preg_replace('/[^A-Za-z0-9\/_|+\- ]+/', '', $text) ?? $text;
            $text = preg_replace('/[\/_|+\- ]+/', $sep, $text) ?? $text;
        } else {
            $text = preg_replace('/[^\p{L}\p{N}\/_|+\- ]+/u', '', $text) ?? $text;
            $text = preg_replace('/[\/_|+\- ]+/u', $sep, $text) ?? $text;
        }

        $text = trim($text, $sep);

        if ($limit !== null && $limit > 0) {
            $text = mb_substr($text, 0, $limit);
            $text = trim($text, $sep);
        }

        if ($lowercase) $text = $locale ? mb_strtolower($text, $locale) : mb_strtolower($text);

        return $text === '' ? 'n-a' : $text;
    }
}
