<?php
namespace Careminate\Database\Blueprint;

use Careminate\Models\Model;

class Blueprint
{
    protected string $table;
    protected string $driver;
    protected array $columns = [];
    protected array $constraints = [];
    protected array $alterations = [];

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->driver = Model::getDriver();
    }

    public function increments(string $column = 'id'): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => 'SERIAL PRIMARY KEY',
            'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            default => 'INT PRIMARY KEY AUTO_INCREMENT',
        };
        return $this;
    }

    public function bigIncrements(string $column = 'id'): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => 'BIGSERIAL PRIMARY KEY',
            'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            default => 'BIGINT PRIMARY KEY AUTO_INCREMENT',
        };
        return $this;
    }

    public function string(string $column, int $length = 255): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => "VARCHAR($length)",
            'sqlite' => "TEXT",
            default => "VARCHAR($length)",
        };
        return $this;
    }

    public function text(string $column): self
    {
        $this->columns[$column] = 'TEXT';
        return $this;
    }

    public function longText(string $column): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => 'TEXT',
            'sqlite' => 'TEXT',
            default => 'LONGTEXT',
        };
        return $this;
    }

    public function integer(string $column, bool $unsigned = false): self
    {
        $unsigned = $unsigned ? ' UNSIGNED' : '';
        $this->columns[$column] = match($this->driver) {
            'pgsql' => 'INTEGER',
            'sqlite' => 'INTEGER',
            default => "INT$unsigned",
        };
        return $this;
    }

    public function bigInteger(string $column, bool $unsigned = false): self
    {
        $unsigned = $unsigned ? ' UNSIGNED' : '';
        $this->columns[$column] = match($this->driver) {
            'pgsql' => 'BIGINT',
            'sqlite' => 'INTEGER',
            default => "BIGINT$unsigned",
        };
        return $this;
    }

    public function decimal(string $column, int $precision = 8, int $scale = 2): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => "DECIMAL($precision, $scale)",
            'sqlite' => 'REAL',
            default => "DECIMAL($precision, $scale)",
        };
        return $this;
    }

    public function boolean(string $column): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => 'BOOLEAN',
            'sqlite' => 'INTEGER',
            default => 'TINYINT(1)',
        };
        return $this;
    }

    public function date(string $column): self
    {
        $this->columns[$column] = 'DATE';
        return $this;
    }

    public function dateTime(string $column, int $precision = 0): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => $precision > 0 ? "TIMESTAMP($precision)" : "TIMESTAMP",
            'sqlite' => "DATETIME",
            default => $precision > 0 ? "DATETIME($precision)" : "DATETIME",
        };
        return $this;
    }

    public function timestamp(string $column, int $precision = 0): self
    {
        return $this->dateTime($column, $precision);
    }

    public function timestamps(): self
    {
        $this->columns['created_at'] = match($this->driver) {
            'pgsql' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'sqlite' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            default => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        };
        $this->columns['updated_at'] = match($this->driver) {
            'pgsql' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'sqlite' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            default => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        };
        return $this;
    }

    public function nullableTimestamps(): self
    {
        $this->columns['created_at'] = match($this->driver) {
            'pgsql' => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP",
            'sqlite' => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
            default => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP",
        };
        $this->columns['updated_at'] = match($this->driver) {
            'pgsql' => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP",
            'sqlite' => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
            default => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        };
        return $this;
    }

    public function softDeletes(): self
    {
        $this->columns['deleted_at'] = match($this->driver) {
            'pgsql' => "TIMESTAMP NULL",
            'sqlite' => "DATETIME NULL",
            default => "TIMESTAMP NULL",
        };
        return $this;
    }

    public function json(string $column): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => 'JSONB',
            'sqlite' => 'TEXT',
            default => 'JSON',
        };
        return $this;
    }

    public function uuid(string $column = 'uuid'): self
    {
        $this->columns[$column] = match($this->driver) {
            'pgsql' => 'UUID',
            'sqlite' => 'TEXT',
            default => 'CHAR(36)',
        };
        return $this;
    }

    public function foreignId(string $column): self
    {
        return $this->bigInteger($column, true);
    }

    // Constraint methods
    public function unique(string $column): self
    {
        $this->constraints[] = "UNIQUE($column)";
        return $this;
    }

    public function index(string $column): self
    {
        $this->constraints[] = "INDEX($column)";
        return $this;
    }

    public function primary(string $column): self
    {
        $this->constraints[] = "PRIMARY KEY($column)";
        return $this;
    }

    public function foreign(string $column): self
    {
        $this->constraints[] = "FOREIGN KEY($column)";
        return $this;
    }

    public function references(string $column): self
    {
        $last = count($this->constraints) - 1;
        if ($last >= 0) {
            $this->constraints[$last] .= " REFERENCES $column";
        }
        return $this;
    }

    public function on(string $table): self
    {
        $last = count($this->constraints) - 1;
        if ($last >= 0) {
            $this->constraints[$last] .= "($table)";
        }
        return $this;
    }

    public function onDelete(string $action): self
    {
        $last = count($this->constraints) - 1;
        if ($last >= 0) {
            $this->constraints[$last] .= " ON DELETE $action";
        }
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $last = count($this->constraints) - 1;
        if ($last >= 0) {
            $this->constraints[$last] .= " ON UPDATE $action";
        }
        return $this;
    }

    public function default($value): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $default = is_string($value) ? "'$value'" : $value;
            $this->columns[$lastColumn] .= " DEFAULT $default";
        }
        return $this;
    }

    public function nullable(): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn] .= " NULL";
        }
        return $this;
    }

    public function notNull(): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn] .= " NOT NULL";
        }
        return $this;
    }

    // Alter table methods
    public function addColumn(string $name, string $type): self
    {
        $this->alterations[] = "ADD COLUMN $name $type";
        return $this;
    }

    public function dropColumn(string $name): self
    {
        $this->alterations[] = match($this->driver) {
            'pgsql', 'sqlite' => "DROP COLUMN $name",
            default => "DROP COLUMN `$name`",
        };
        return $this;
    }

    public function renameColumn(string $old, string $new): self
    {
        switch ($this->driver) {
            case 'pgsql':
                $this->alterations[] = "RENAME COLUMN $old TO $new";
                break;
            case 'sqlite':
                $this->alterations[] = ['rename' => [$old, $new]];
                break;
            default: // mysql
                // For MySQL, we need the column type - this is a limitation
                // You might want to enhance this to accept type as parameter
                $this->alterations[] = "CHANGE `$old` `$new` VARCHAR(255)";
        }
        return $this;
    }

    public function modifyColumn(string $name, string $newType): self
    {
        $this->alterations[] = match($this->driver) {
            'pgsql' => "ALTER COLUMN $name TYPE $newType",
            'sqlite' => "MODIFY COLUMN $name $newType", // SQLite has limited ALTER support
            default => "MODIFY `$name` $newType",
        };
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getAlterations(): array
    {
        return $this->alterations;
    }
}