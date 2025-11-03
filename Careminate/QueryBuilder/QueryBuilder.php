<?php declare (strict_types = 1);
namespace Careminate\QueryBuilder;

use Careminate\Models\Model;
use PDO;

class QueryBuilder
{
    protected string $modelClass;
    protected array $wheres = [];
    protected array $bindings = [];
    protected ?string $orderBy = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $columns = ['*'];

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->wheres[] = "$column $operator :$column";
        $this->bindings[":$column"] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "$column $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function get(): array
    {
        $modelClass = $this->modelClass;
        $table = $modelClass::table();

        // Build SELECT clause
        $columns = implode(', ', $this->columns);
        $sql = "SELECT $columns FROM $table";
        
        // Add WHERE clause
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        
        // Add ORDER BY clause
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . $this->orderBy;
        }
        
        // Add LIMIT clause
        if (!empty($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        
        // Add OFFSET clause
        if (!empty($this->offset)) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        $stmt = $modelClass::pdo()->prepare($sql);
        $stmt->execute($this->bindings);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) use ($modelClass) {
            $model = new $modelClass();
            return $model->fill($row);
        }, $results);
    }

    public function first(): ?object
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    public function insert(array $data): int
    {
        $modelClass = $this->modelClass;
        $table = $modelClass::table();

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $modelClass::pdo()->prepare($sql);
        $stmt->execute($data);

        return (int) $modelClass::pdo()->lastInsertId();
    }

    public function update(array $data): int
    {
        $modelClass = $this->modelClass;
        $table = $modelClass::table();

        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :set_$key";
            $this->bindings[":set_$key"] = $value;
        }

        $sql = "UPDATE $table SET " . implode(', ', $setParts);
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = $modelClass::pdo()->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $modelClass = $this->modelClass;
        $table = $modelClass::table();

        $sql = "DELETE FROM $table";
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = $modelClass::pdo()->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }

    /**
     * Get the count of records
     */
    public function count(): int
    {
        $modelClass = $this->modelClass;
        $table = $modelClass::table();

        $sql = "SELECT COUNT(*) as count FROM $table";
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = $modelClass::pdo()->prepare($sql);
        $stmt->execute($this->bindings);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Check if any records exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }
}