<?php declare (strict_types = 1);
namespace Careminate\Database\Connections\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class ConnectionFactory
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function create(?string $name = null): Connection
    {
        // 1. If DATABASE_URL exists, prioritize it
        $databaseUrl = env('DATABASE_URL', null);
        if (!empty($databaseUrl)) {
            return DriverManager::getConnection(['url' => $databaseUrl]);
        }

        // 2. Otherwise, use driver-based config
        $name = $name ?: $this->config['default'];
        $connectionConfig = $this->config['connections'][$name] ?? null;

        if (!$connectionConfig) {
            throw new \InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        // 3. Doctrine accepts either a 'url' or full parameters array
        if (!empty($connectionConfig['url'])) {
            return DriverManager::getConnection(['url' => $connectionConfig['url']]);
        }

        return DriverManager::getConnection($connectionConfig);
    }
}
