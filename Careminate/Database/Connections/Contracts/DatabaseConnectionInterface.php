<?php declare(strict_types=1);
namespace Careminate\Database\Connections\Contracts;

interface DatabaseConnectionInterface
{
    public function getPDO(): \PDO;
}


