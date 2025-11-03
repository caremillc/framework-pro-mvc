<?php
namespace Careminate\Database\Schema;

use PDO;
use Careminate\Models\Model;
use Careminate\Database\Blueprint\Blueprint;

class Schema
{
    protected PDO $db;
    protected string $driver;

    public function __construct()
    {
        $this->db = Model::getConnection();
        $this->driver = Model::getDriver();
    }

    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $columns = [];
        foreach ($blueprint->getColumns() as $name => $type) {
            $columns[] = $this->quoteIdentifier($name) . " $type";
        }

        $constraints = $blueprint->getConstraints();
        
        $sql = "CREATE TABLE IF NOT EXISTS " . $this->quoteIdentifier($table) . 
               " (" . implode(', ', array_merge($columns, $constraints)) . ")";

        $this->db->prepare($sql)->execute();
    }

    public function drop(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS " . $this->quoteIdentifier($table);
        $this->db->prepare($sql)->execute();
    }

    public function dropIfExists(string $table): void
    {
        $this->drop($table);
    }

    public function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        foreach ($blueprint->getAlterations() as $alter) {
            if (is_array($alter) && isset($alter['rename']) && $this->driver === 'sqlite') {
                [$old, $new] = $alter['rename'];
                $this->renameSQLiteColumn($table, $old, $new);
                continue;
            }
            
            $sql = "ALTER TABLE " . $this->quoteIdentifier($table) . " $alter";
            $this->db->prepare($sql)->execute();
        }
    }

    public function hasTable(string $table): bool
    {
        return match($this->driver) {
            'pgsql' => $this->checkPostgresTable($table),
            'sqlite' => $this->checkSQLiteTable($table),
            default => $this->checkMySQLTable($table),
        };
    }

    public function hasColumn(string $table, string $column): bool
    {
        return match($this->driver) {
            'pgsql' => $this->checkPostgresColumn($table, $column),
            'sqlite' => $this->checkSQLiteColumn($table, $column),
            default => $this->checkMySQLColumn($table, $column),
        };
    }

    protected function renameSQLiteColumn(string $table, string $old, string $new): void
    {
        $sql = "ALTER TABLE " . $this->quoteIdentifier($table) . 
               " RENAME COLUMN " . $this->quoteIdentifier($old) . 
               " TO " . $this->quoteIdentifier($new);
        $this->db->prepare($sql)->execute();
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return match($this->driver) {
            'pgsql', 'sqlite' => "\"$identifier\"",
            default => "`$identifier`",
        };
    }

    // Database specific table/column checks
    private function checkMySQLTable(string $table): bool
    {
        $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }

    private function checkPostgresTable(string $table): bool
    {
        $stmt = $this->db->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }

    private function checkSQLiteTable(string $table): bool
    {
        $stmt = $this->db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }

    private function checkMySQLColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetchColumn();
    }

    private function checkPostgresColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name=? AND column_name=?");
        $stmt->execute([$table, $column]);
        return (bool) $stmt->fetchColumn();
    }

    private function checkSQLiteColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare("PRAGMA table_info($table)");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            if ($col['name'] === $column) {
                return true;
            }
        }
        return false;
    }
}