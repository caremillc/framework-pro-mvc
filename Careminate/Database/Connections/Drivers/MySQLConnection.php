<?php declare (strict_types = 1);
namespace Careminate\Database\Connections\Drivers;

use Careminate\Database\Connections\Contracts\DatabaseConnectionInterface;
use Exception;
use PDO;

class MySQLConnection implements DatabaseConnectionInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $config = config('database.drivers')['mysql'];
        try {
            $dsn       = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $this->pdo = new PDO($dsn, $config['username'], $config['password']);
            $this->pdo->setAttribute($config['ERRMODE'], $config['EXCEPTION']);
        } catch (Exception $e) {
            echo 'MySQL Connection Error: ' . $e->getMessage();
            throw $e;
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}
