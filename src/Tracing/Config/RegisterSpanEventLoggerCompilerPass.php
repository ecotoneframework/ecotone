<?php

declare(strict_types=1);

namespace Ecotone\Tracing\Config;

use Ecotone\Messaging\Config\Container\Compiler\CompilerPass;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Logger\SubscribableLoggingGateway;
use Ecotone\Tracing\SpanEventLogger;
use Ecotone\Tracing\Tracer;

use function is_a;

/**
 * licence Apache-2.0
 */
final class RegisterSpanEventLoggerCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $builder): void
    {
        if (! $builder->has(LoggingGateway::class)) {
            return;
        }

        $loggingGateway = $builder->getDefinition(LoggingGateway::class);
        if (is_a($loggingGateway->getClassName(), SubscribableLoggingGateway::class, true)) {
            $loggingGateway->addMethodCall('registerLogger', [new Definition(SpanEventLogger::class, [new Reference(Tracer::class)])]);
        }
    }
}
