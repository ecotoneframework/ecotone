<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class InMemoryRouteResolver implements RouteResolver
{
    /**
     * @param array<string, MessageProcessor> $routeMap
     */
    public function __construct(
        private array $routeMap
    ) {
    }

    public function resolve(string $routeName): MessageProcessor
    {
        return $this->routeMap[$routeName] ?? throw InvalidArgumentException::create("No route found for name {$routeName}");
    }
}
