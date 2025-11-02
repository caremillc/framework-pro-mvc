<?php declare (strict_types = 1);
namespace Careminate\Database\Connections\Contracts;

use Doctrine\DBAL\Connection;

interface ConnectionInterface 
{
    public function create(): Connection;
}