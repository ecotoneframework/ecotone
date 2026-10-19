<?php

declare(strict_types=1);

namespace Ecotone\Tracing\Config;

use Ecotone\Messaging\Config\Container\Compiler\CompilerPass;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Modelling\Config\MessageHandlerRoutingModule;
use Ecotone\Tracing\MessageSpanAttributes;
use Ecotone\Tracing\Tracer;
use Ecotone\Tracing\TracingRouteResolver;

/**
 * licence Apache-2.0
 */
final class TraceEventRoutesCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $builder): void
    {
        if (! $builder->has(MessageHandlerRoutingModule::EVENT_BUS_ROUTE_RESOLVER)) {
            return;
        }

        $builder->replace(MessageHandlerRoutingModule::EVENT_BUS_ROUTE_RESOLVER, new Definition(TracingRouteResolver::class, [
            $builder->getDefinition(MessageHandlerRoutingModule::EVENT_BUS_ROUTE_RESOLVER),
            new Reference(Tracer::class),
            new Reference(MessageSpanAttributes::class),
        ]));
    }
}
