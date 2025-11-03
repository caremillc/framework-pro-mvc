<?php declare(strict_types=1);

namespace Careminate\Console\Commands;

use Careminate\Database\Migrations\MigrationRunner;
use Careminate\Console\Commands\CommandInterface;

class MigrateRollback implements CommandInterface
{
    protected string $name = 'database:rollback';
    protected string $description = 'Rollback the last batch of migrations';

    public function execute(array $params = []): int
    {
        $path = BASE_PATH . '/database/migrations';

        echo "Rolling back migrations from: {$path}\n";

        try {
            $runner = new MigrationRunner();
            $runner->rollback($path);
            echo "✅ Rollback completed successfully.\n";
            return 0;
        } catch (\Throwable $e) {
            echo "❌ Rollback failed: " . $e->getMessage() . "\n";
            return 1;
        }
    }
}
