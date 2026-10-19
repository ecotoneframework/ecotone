<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Router\RouteResolver;

/**
 * licence Apache-2.0
 */
final class TracingRouteResolver implements RouteResolver
{
    public function __construct(
        private RouteResolver $routeResolver,
        private Tracer $tracer,
        private MessageSpanAttributes $messageSpanAttributes,
    ) {
    }

    public function resolve(string $routeName): MessageProcessor
    {
        return new TracedRouteProcessor($routeName, $this->routeResolver->resolve($routeName), $this->tracer, $this->messageSpanAttributes);
    }
}
