<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithLogger;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Handler\Logger\Annotation\LogAfter;
use Ecotone\Messaging\Handler\Logger\Annotation\LogBefore;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;
use Ecotone\Messaging\Handler\Logger\LoggingLevel;

class ServiceActivatorWithLoggerExample
{
    #[ServiceActivator("inputChannel", "test-name")]
    #[
        LogBefore(LoggingLevel::INFO, true),
        LogAfter(LoggingLevel::INFO, true),
        LogError(LoggingLevel::CRITICAL, true)
    ]
    public function sendMessage(): void
    {
    }
}