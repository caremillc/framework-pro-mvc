<?php declare (strict_types = 1);
namespace Careminate\Database\Connections\Drivers;

use Careminate\Database\Connections\Contracts\DatabaseConnectionInterface;
use Exception;
use PDO;

class SQLiteConnection implements DatabaseConnectionInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $config = config('database.drivers')['sqlite'];
        try {
            $this->pdo = new PDO("sqlite:{$config['path']}");
            $this->pdo->setAttribute($config['ERRMODE'], $config['EXCEPTION']);
        } catch (Exception $e) {
            echo 'SQLite Connection Error: ' . $e->getMessage();
            throw $e;
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}
