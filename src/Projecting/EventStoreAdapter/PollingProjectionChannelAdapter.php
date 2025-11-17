<?php

namespace Ecotone\Projecting\EventStoreAdapter;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Enterprise
 */
class PollingProjectionChannelAdapter implements DefinedObject
{
    public function execute(): bool
    {
        // This is executed by the inbound channel adapter, which then sends the result
        // to the projection's input channel, triggering ProjectingManager::execute()
        return true;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
