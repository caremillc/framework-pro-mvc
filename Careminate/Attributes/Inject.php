<?php declare(strict_types=1);

namespace Careminate\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Inject
{
    public function __construct(public string $service)
    {
    }
}
