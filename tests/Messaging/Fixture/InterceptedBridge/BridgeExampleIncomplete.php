<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\InterceptedBridge;

use Ecotone\Messaging\Attribute\ServiceActivator;

/**
 * licence Apache-2.0
 */
final class BridgeExampleIncomplete
{
    #[ServiceActivator('bridgeExample', outputChannelName: 'bridgeSum')]
    public function result(int $result): int
    {
        return $result;
    }
}
