<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithLogger;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\LogAfter;
use Ecotone\Api\Attribute\LogBefore;
use Ecotone\Api\Attribute\LogError;
use Ecotone\Api\Logging\LoggingLevel;

/**
 * licence Apache-2.0
 */
class ServiceActivatorWithLoggerExample
{
    #[InternalHandler('inputChannel', endpointId: 'test-name')]
    #[
        LogBefore(LoggingLevel::INFO, true),
        LogAfter(LoggingLevel::INFO, true),
        LogError(LoggingLevel::CRITICAL, true)
    ]
    public function sendMessage(): void
    {
    }
}
