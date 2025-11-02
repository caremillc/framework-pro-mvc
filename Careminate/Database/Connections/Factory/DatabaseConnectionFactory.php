<?php declare (strict_types = 1);

namespace Careminate\Database\Connections\Factory;

use Careminate\Database\Connections\Contracts\DatabaseConnectionInterface;
use Careminate\Database\Connections\Drivers\PostgresConnection;
use Careminate\Database\Connections\Drivers\SQLiteConnection;
use Careminate\Database\Drivers\MySQLConnection;
use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\DriverManager;
use Exception;
use PDO;

class DatabaseConnectionFactory
{
    /**
     * Make a raw PDO connection based on config.
     */
    public static function makePDO(): DatabaseConnectionInterface
    {
        $default = config('database.default', 'mysql');

        return match ($default) {
            'mysql'  => new MySQLConnection(),
            'sqlite' => new SQLiteConnection(),
            'pgsql', 'postgres', 'postgresql' => new PostgresConnection(),
            default  => throw new Exception("Unsupported database driver: {$default}"),
        };
    }

    /**
     * Make a Doctrine DBAL connection based on config.
     */
    public static function makeDBAL(): DBALConnection
    {
        $default = config('database.default', 'mysql');
        $config  = config('database.drivers')[$default] ?? [];

        $params = [];

        switch ($default) {
            case 'mysql':
                $params = [
                    'driver'              => 'pdo_mysql',
                    'host'                => $config['host'] ?? '127.0.0.1',
                    'port'                => $config['port'] ?? 3306,
                    'dbname'              => $config['database'] ?? 'careminate',
                    'user'                => $config['username'] ?? 'root',
                    'password'            => $config['password'] ?? '',
                    'charset'             => $config['charset'] ?? 'utf8mb4',
                    'defaultTableOptions' => [
                        'charset' => $config['charset'] ?? 'utf8mb4',
                        'collate' => $config['collate'] ?? 'utf8mb4_unicode_ci',
                    ],
                ];
                break;

            case 'sqlite':
                $path = $config['path'] ?? __DIR__ . '/../../storage/database.sqlite';
                if (! file_exists($path)) {
                    touch($path); // create the file if missing
                }
                $params = [
                    'driver' => 'pdo_sqlite',
                    'path'   => $path,
                ];
                break;

            case 'pgsql':
            case 'postgres':
            case 'postgresql':
                $params = [
                    'driver'   => 'pdo_pgsql',
                    'host'     => $config['host'] ?? '127.0.0.1',
                    'port'     => $config['port'] ?? 5432,
                    'dbname'   => $config['database'] ?? 'careminate',
                    'user'     => $config['username'] ?? 'postgres',
                    'password' => $config['password'] ?? '',
                    'charset'  => $config['charset'] ?? 'utf8',
                    'sslmode'  => $config['sslmode'] ?? 'prefer',
                ];
                break;

            default:
                throw new Exception("Unsupported database driver: {$default}");
        }

        // Ensure driver key exists (Doctrine requires this)
        if (! isset($params['driver'])) {
            throw new Exception("Missing 'driver' parameter for DBAL connection");
        }

        // Create Doctrine DBAL connection
        return DriverManager::getConnection($params);
    }
}
