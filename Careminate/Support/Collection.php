<?php declare (strict_types = 1);

namespace Careminate\Support;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * ================================
 * COLLECTION
 * ================================
 */
class Collection implements ArrayAccess, IteratorAggregate, Countable
{ 
    use Macroable;

    protected array $items = [];


 

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function all(): array
    {return $this->items;}

       
    public function getIterator(): Traversable { yield from $this->items; }
    

    public function count(): int { return count($this->items); }

    
    public static function make(array $items = []): static
    {
        return new static($items);
    }

    public function map(callable $callback): static { return new static(array_map($callback, $this->items, array_keys($this->items))); }
    public function filter(?callable $callback = null): static { return new static($callback ? array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH) : array_filter($this->items)); }
    public function first(?callable $callback = null, mixed $default = null): mixed { return Arr::first($this->items, $callback, $default); }
    public function last(?callable $callback = null, mixed $default = null): mixed { return Arr::last($this->items, $callback, $default); }
    public function flatten(int $depth = PHP_INT_MAX): static { return new static(Arr::flatten($this->items, $depth)); }

    /**
     * Sum of the collection values.
     */
  public function sum(string|callable|null $key = null): float|int
    {
        if ($key === null) return array_sum($this->items);
        return array_sum(array_map(fn($item) => is_callable($key) ? $key($item) : Arr::get((array)$item, $key), $this->items));
    }

/**
 * Average (mean) of the collection values.
 */
       public function avg(string|callable|null $key = null): float { return count($this->items) === 0 ? 0 : $this->sum($key) / count($this->items); }


/**
 * Maximum value of the collection.
 */
       public function max(string|callable|null $key = null): mixed { return max(array_map(fn($item) => is_callable($key) ? $key($item) : Arr::get((array)$item, $key), $this->items)); }


/**
 * Minimum value of the collection.
 */
       public function min(string|callable|null $key = null): mixed { return min(array_map(fn($item) => is_callable($key) ? $key($item) : Arr::get((array)$item, $key), $this->items)); }


/**
 * Median value of the collection.
 */
    public function median(callable | string | null $key = null): mixed
    {
        $values = $key === null
            ? $this->items
            : array_map(fn($item) => is_array($item) ? ($item[$key] ?? 0) : ($item->{$key} ?? 0), $this->items);

        sort($values);
        $count = count($values);

        if ($count === 0) {
            return null;
        }

        $middle = (int) floor(($count - 1) / 2);

        if ($count % 2) {
            return $values[$middle];
        }

        return ($values[$middle] + $values[$middle + 1]) / 2;
    }

    /**
     * Group collection items by a given key or callback.
     */
   

/**
 * Key the collection by a given key or callback.
 */
     public function keyBy(string|callable $key, bool $slugKeys = false): static
    {
        $results = [];
        foreach ($this->items as $item) {
            $itemKey = is_callable($key) ? $key($item) : Arr::get((array)$item, $key);
            if ($slugKeys) $itemKey = Str::slug((string)$itemKey);
            $results[$itemKey] = $item;
        }
        return new static($results);
    }

    /**
     * Sort the collection by a given key or callback (ascending).
     */
    public function sortBy(callable | string $key, bool $ascending = true): static
    {
        $items = $this->items;

        usort($items, function ($a, $b) use ($key, $ascending) {
            $valueA = is_callable($key)
                ? $key($a)
                : (is_array($a) ? ($a[$key] ?? null) : ($a->{$key} ?? null));
            $valueB = is_callable($key)
                ? $key($b)
                : (is_array($b) ? ($b[$key] ?? null) : ($b->{$key} ?? null));

            if ($valueA == $valueB) {
                return 0;
            }

            return ($valueA < $valueB ? -1 : 1) * ($ascending ? 1 : -1);
        });

        return new static($items);
    }

/**
 * Sort the collection by a given key or callback (descending).
 */
    public function sortByDesc(callable | string $key): static
    {
        return $this->sortBy($key, false);
    }

/**
 * Remove duplicate items from the collection.
 * Optionally provide a key or callback for uniqueness.
 */
    public function unique(callable | string | null $key = null): static
    {
        $seen    = [];
        $results = [];

        foreach ($this->items as $item) {
            $compare = match (true) {
                is_callable($key) => $key($item),
                is_string($key)   => is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null),
                default           => $item
            };

            if (! in_array($compare, $seen, true)) {
                $seen[]    = $compare;
                $results[] = $item;
            }
        }

        return new static($results);
    }

/**
 * Reverse the order of items in the collection.
 */
    public function reverse(): static
    {
        return new static(array_reverse($this->items, true));
    }

/**
 * Get a value from an array or object using dot notation.
 */
    protected function getValue(mixed $item, string | callable $key): mixed
    {
        if (is_callable($key)) {
            return $key($item);
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($item) && array_key_exists($segment, $item)) {
                $item = $item[$segment];
            } elseif (is_object($item) && isset($item->{$segment})) {
                $item = $item->{$segment};
            } else {
                return null;
            }
        }

        return $item;
    }

   public function pluck(string|callable $key, bool $slugKeys = false): static
    {
        $results = [];
        foreach ($this->items as $item) {
            $value = is_callable($key) ? $key($item) : Arr::get((array)$item, $key);
            $results[] = $slugKeys ? Str::slug((string)$value) : $value;
        }
        return new static($results);
    }
        public function groupBy(string|callable $key, bool $slugKeys = false): static
    {
        $results = [];
        foreach ($this->items as $item) {
            $groupKey = is_callable($key) ? $key($item) : Arr::get((array)$item, $key);
            if ($slugKeys) $groupKey = Str::slug((string)$groupKey);
            $results[$groupKey][] = $item;
        }
        return new static($results);
    }
    public function get(string | int | null $key, mixed $default = null): mixed
    {return Arr::get($this->items, $key, $default);}
    public function set(string | int $key, mixed $value): static
    {Arr::set($this->items, $key, $value);return $this;}
    public function has(string | int $key): bool
    {return Arr::has($this->items, $key);}
    public function forget(string | int $key): static
    {Arr::forget($this->items, $key);return $this;}
   
       public function where(callable $callback): static
    {return new static(Arr::where($this->items, $callback));}
   
       public function shuffle(): static { $items = $this->items; shuffle($items); return new static($items); }

     public function random(int $amount = 1): mixed
    {
        if ($amount === 1) return $this->items[array_rand($this->items)] ?? null;
        $items = $this->items; shuffle($items); return new static(array_slice($items, 0, $amount));
    }
   public function collapse(): static
    {
        $results = [];
        foreach ($this->items as $values) { $results = array_merge($results, $values instanceof self ? $values->all() : (array)$values); }
        return new static($results);
    }
    public function pull(string | int $key, mixed $default = null): mixed
    {return Arr::pull($this->items, $key, $default);}
    public function push(mixed $value): static
    { $this->items[] = $value;return $this;}

     public function tap(callable $callback): static { $callback($this); return $this; }

    public function pipe(callable $callback): mixed { return $callback($this); }



    public function isEmpty(): bool
    {return empty($this->items);}
    public function toJson(int $flags = 0): string
    {return json_encode($this->items, $flags);}

    /* Interfaces */
    public function offsetExists(mixed $offset): bool
    {return isset($this->items[$offset]);}
    public function offsetGet(mixed $offset): mixed
    {return $this->items[$offset] ?? null;}
    public function offsetSet(mixed $offset, mixed $value): void
    {$offset === null ? $this->items[] = $value : $this->items[$offset] = $value;}
    public function offsetUnset(mixed $offset): void
    {unset($this->items[$offset]);}
   
}
