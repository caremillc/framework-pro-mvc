<?php declare (strict_types = 1);
namespace Careminate\Models;

use Careminate\QueryBuilder\QueryBuilder;
use Exception;
use PDO;

abstract class Model
{
    /**
     * PDO connection instance (shared by all models)
     */
    protected static ?PDO $db = null;

    /**
     * Table name
     */
    protected static ?string $table = null;

    /**
     * Primary key column
     */
    protected static string $primaryKey = 'id';

    /**
     * Fillable columns for mass assignment
     */
    protected array $fillable = [];

    /**
     * Set the PDO connection (call once in bootstrap)
     */
    public static function setConnection(PDO $pdo): void
    {
        self::$db = $pdo;
    }

    /**
     * Get PDO connection (alias for pdo())
     */
    public static function getConnection(): PDO
    {
        return self::pdo();
    }

/**
 * Get database driver name
 */
    public static function getDriver(): string
    {
        return self::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
    /**
     * Get PDO connection
     */
    public static function pdo(): PDO
    {
        if (! self::$db) {
            throw new Exception("Database connection not initialized. Call Model::setConnection() first.");
        }
        return self::$db;
    }

    /**
     * Get table name (auto-resolve if not defined)
     */
    public static function table(): string
    {
        if (isset(static::$table)) {
            return static::$table;
        }

        $class                = static::class;
        $parts                = explode('\\', $class);
        $name                 = strtolower(end($parts));
        return static::$table = $name . 's';
    }

    /**
     * Start a new QueryBuilder instance
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::class);
    }

    /**
     * Retrieve all records
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    /**
     * Find record by primary key
     */
    public static function find(int | string $id): ?object
    {
        return static::query()->where(static::$primaryKey, '=', $id)->first();
    }

    /**
     * Create a new record
     */
    public static function create(array $attributes): static
    {
        $instance = new static();

        if (! empty($instance->fillable)) {
            $attributes = array_intersect_key($attributes, array_flip($instance->fillable));
        }

        $id = static::query()->insert($attributes);
        return static::find($id);
    }

    /**
     * Update the current model
     */
    public function update(array $attributes): bool
    {
        if (! property_exists($this, static::$primaryKey)) {
            throw new Exception("Primary key not found on model " . static::class);
        }

        if (! empty($this->fillable)) {
            $attributes = array_intersect_key($attributes, array_flip($this->fillable));
        }

        $affected = static::query()
            ->where(static::$primaryKey, '=', $this->{static::$primaryKey})
            ->update($attributes);

        if ($affected) {
            foreach ($attributes as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }

        return false;
    }

    /**
     * Delete the current model
     */
    public function delete(): bool
    {
        if (! property_exists($this, static::$primaryKey)) {
            throw new Exception("Primary key not found on model " . static::class);
        }

        $deleted = static::query()
            ->where(static::$primaryKey, '=', $this->{static::$primaryKey})
            ->delete();

        return $deleted > 0;
    }

    /**
     * Fill model properties dynamically
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
     * Magic getter
     */
    public function __get(string $key)
    {
        return $this->$key ?? null;
    }

    /**
     * Magic setter
     */
    public function __set(string $key, $value): void
    {
        $this->$key = $value;
    }
}
