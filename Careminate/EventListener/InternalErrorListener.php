<?php 
namespace Careminate\EventListener;

use Careminate\EventDispatcher\ResponseEvent;

class InternalErrorListener
{
    private const INTERNAL_ERROR_MIN_VALUE = 499;

    public function __invoke(ResponseEvent $event): void
    {
        $status = $event->getResponse()->getStatus();

        if ($status > self::INTERNAL_ERROR_MIN_VALUE) {
            $event->stopPropagation();
        }
    }
}