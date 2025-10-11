<?php declare(strict_types=1);

namespace Careminate\Routing\Contracts;

use Careminate\Http\Requests\Request;

interface RouterInterface
{
    /**
     * Dispatch the request to a route handler.
     *
     * @param Request $request
     * @return array [$callableHandler, $routeVars]
     */
    public function dispatch(Request $request): array;
}