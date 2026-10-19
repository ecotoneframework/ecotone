<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Bridge;

use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;

/**
 * licence Apache-2.0
 */
final class BridgeBuilder
{
    public static function create(): MessageProcessorActivatorBuilder
    {
        return MessageProcessorActivatorBuilder::createForMethod(Bridge::class, 'handle');
    }
}
