<?php declare (strict_types = 1);
namespace Careminate\Database\Connections\Drivers;

use Careminate\Database\Connections\Contracts\DatabaseConnectionInterface;
use Exception;
use PDO;

class PostgresConnection implements DatabaseConnectionInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $config = config('database.drivers')['pgsql'];
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;options=--client_encoding=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset'] ?? 'utf8'
            );

            if (! empty($config['sslmode'])) {
                $dsn .= ";sslmode={$config['sslmode']}";
            }

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => $config['persistent'] ?? false,
            ];

            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (Exception $e) {
            echo 'PostgreSQL Connection Error: ' . $e->getMessage();
            throw $e;
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}
