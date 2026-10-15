<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller;

use Ecotone\Api\Attribute\InternalHandler;

/**
 * licence Apache-2.0
 */
class ServiceActivatorWithPollerExample
{
    #[InternalHandler('inputChannel', endpointId: 'test-name')]
    public function sendMessage(): void
    {
    }
}
