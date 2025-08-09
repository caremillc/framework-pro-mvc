<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Contracts;

/**
 * @codeCoverageIgnore
 * @deprecated Use Psr\Http\Message\ResponseInterface instead
 */
interface PsrResponseInterface extends \Psr\Http\Message\ResponseInterface
{
    // This intentionally left blank - just extends the PSR interface
    // Only create this if you need to add custom methods
}