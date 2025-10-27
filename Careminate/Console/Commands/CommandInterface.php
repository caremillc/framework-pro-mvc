<?php declare (strict_types = 1);
namespace Careminate\Console\Commands;

interface CommandInterface
{
    public function execute(array $params = []): int;
}