<?php declare(strict_types=1);
namespace Careminate\EventDispatcher;

use Careminate\EntityManager\Entity;

class PostPersist extends Event
{
    public function __construct(private Entity $subject)
    {
    }
}
