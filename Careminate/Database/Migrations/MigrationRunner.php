<?php
namespace Careminate\Database\Migrations;

use Careminate\Models\Model;
use Exception;
use PDO;

class MigrationRunner
{
    protected PDO $db;
    protected string $migrationsTable = 'migrations';

    public function __construct()
    {
        // ensure Model connection initialized
        $this->db = Model::getConnection();
        $this->ensureMigrationsTable();
    }

    protected function ensureMigrationsTable(): void
    {
        $driver = Model::getDriver();

        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS \"{$this->migrationsTable}\" (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration TEXT NOT NULL,
            batch INTEGER NOT NULL,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        } else {
            $sql = match ($driver) {
                'pgsql' => "CREATE TABLE IF NOT EXISTS \"{$this->migrationsTable}\" (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
                default => "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            };
        }

        $this->db->exec($sql);
    }

    /**
     * Run all pending migrations in a directory
     * @param string $path absolute or relative path to migrations folder
     */
    public function migrate(string $path): void
    {
        $applied = $this->getAppliedMigrations();
        $files   = $this->getMigrationFiles($path);

        if (empty($files)) {
            echo "No migration files found in {$path}\n";
            return;
        }

        $batch = $this->getNextBatchNumber();

        foreach ($files as $file) {
            $migrationName = $this->migrationClassNameFromFile($file);

            if (in_array($migrationName, $applied, true)) {
                continue; // already applied
            }

            // load file
            require_once $file;

            if (! class_exists($migrationName)) {
                echo "Migration class {$migrationName} not found in file {$file}, skipping.\n";
                continue;
            }

            /** @var Migration $migrationInstance */
            $migrationInstance = new $migrationName();

            try {
                $this->db->beginTransaction();
                $migrationInstance->up();
                $this->recordMigration($migrationName, $batch);
                $this->db->commit();
                echo "Migrated: {$migrationName}\n";
            } catch (Exception $e) {
                $this->db->rollBack();
                echo "Failed to migrate {$migrationName}: " . $e->getMessage() . PHP_EOL;
                // stop on failure
                throw $e;
            }
        }

        echo "Migrations complete (batch {$batch}).\n";
    }

    /**
     * Rollback last batch
     */
    public function rollback(string $path): void
    {
        $lastBatch = $this->getLastBatch();
        if ($lastBatch <= 0) {
            echo "Nothing to rollback.\n";
            return;
        }

        $migrations = $this->getMigrationsByBatch($lastBatch);

        // rollback in reverse order (last applied first)
        rsort($migrations);

        foreach ($migrations as $migrationName) {
            // find file for this migration
            $file = $this->findFileForMigration($path, $migrationName);
            if (! $file) {
                echo "Migration file for {$migrationName} not found, skipping rollback of that migration.\n";
                $this->removeMigrationRecord($migrationName);
                continue;
            }

            require_once $file;
            if (! class_exists($migrationName)) {
                echo "Migration class {$migrationName} not found in file {$file}, skipping.\n";
                $this->removeMigrationRecord($migrationName);
                continue;
            }

            /** @var Migration $migrationInstance */
            $migrationInstance = new $migrationName();

            try {
                $this->db->beginTransaction();
                $migrationInstance->down();
                $this->removeMigrationRecord($migrationName);
                $this->db->commit();
                echo "Rolled back: {$migrationName}\n";
            } catch (Exception $e) {
                $this->db->rollBack();
                echo "Failed to rollback {$migrationName}: " . $e->getMessage() . PHP_EOL;
                throw $e;
            }
        }

        echo "Rollback of batch {$lastBatch} complete.\n";
    }

    /* ---------------- helpers ---------------- */

    protected function getMigrationFiles(string $path): array
    {
        $fullPath = rtrim($path, DIRECTORY_SEPARATOR);
        if (! is_dir($fullPath)) {
            return [];
        }

        $files = glob($fullPath . DIRECTORY_SEPARATOR . '*.php');
        // sort files natural order (timestamp prefixes)
        natsort($files);
        return array_values($files);
    }

    protected function migrationClassNameFromFile(string $file): string
    {
        // assume filename like 2025_10_26_000001_create_users_table.php
        $basename = pathinfo($file, PATHINFO_FILENAME);
        // remove timestamp prefix and convert to StudlyCase: create_users_table -> CreateUsersTable
        // If the file uses a pure class name, just detect the tail part
        $parts = preg_split('/\d+_|_/', $basename);
        // Try to detect the last portion after timestamp; fallback to basename
        // Simplify: take after first underscore group that contains letters:
        $matches = [];
        if (preg_match('/\d+_\d+_\d+_\d+_(.+)$/', $basename, $matches) && ! empty($matches[1])) {
            $tail = $matches[1];
        } else {
            // fallback: try to strip leading digits and underscores
            $tail = preg_replace('/^[0-9_]+/', '', $basename);
        }

        // convert snake_case to StudlyCase
        $parts = explode('_', $tail);
        $class = implode('', array_map('ucfirst', $parts));
        return $class;
    }

    protected function recordMigration(string $migration, int $batch): void
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $batch]);
    }

    protected function removeMigrationRecord(string $migration): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
        $stmt->execute([$migration]);
    }

    protected function getAppliedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    protected function getNextBatchNumber(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) as b FROM {$this->migrationsTable}");
        $row  = $stmt->fetch(PDO::FETCH_OBJ);
        $max  = $row->b ?? 0;
        return ((int) $max) + 1;
    }

    protected function getLastBatch(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) as b FROM {$this->migrationsTable}");
        $row  = $stmt->fetch(PDO::FETCH_OBJ);
        return (int) ($row->b ?? 0);
    }

    protected function getMigrationsByBatch(int $batch): array
    {
        $stmt = $this->db->prepare("SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id ASC");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    protected function findFileForMigration(string $path, string $migrationClass): ?string
    {
        $files = $this->getMigrationFiles($path);
        foreach ($files as $file) {
            $class = $this->migrationClassNameFromFile($file);
            if ($class === $migrationClass) {
                return $file;
            }

        }
        return null;
    }
}
