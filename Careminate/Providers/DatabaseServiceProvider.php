<?php declare(strict_types=1);
namespace Careminate\Providers;

use PDO;
use Careminate\Support\Config;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('db', function () {
            $default = Config::get('database.default');
            $config  = Config::get("database.connections.{$default}");

            if (!$config) {
                throw new \RuntimeException("Database configuration for [{$default}] not found.");
            }

            if ($config['driver'] === 'sqlite') {
                $dsn = "sqlite:" . $config['database'];
                return new PDO($dsn);
            }

            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            );

            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            return $pdo;
        });
    }
}
