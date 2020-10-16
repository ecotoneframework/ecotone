<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithLogger;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\Handler\Logger\Annotation\LogAfter;
use Ecotone\Messaging\Handler\Logger\Annotation\LogBefore;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;
use Ecotone\Messaging\Handler\Logger\LoggingLevel;

class ServiceActivatorWithLoggerExample
{
    /**
     * @return void
     * @ServiceActivator(
     *     endpointId="test-name",
     *     inputChannelName="inputChannel"
     * )
     * @LogBefore(logLevel=LoggingLevel::INFO, logFullMessage=true)
     * @LogAfter(logLevel=LoggingLevel::INFO, logFullMessage=true)
     * @LogError(logFullMessage=LoggingLevel::CRITICAL, logFullMessage=true)
     */
    public function sendMessage(): void
    {
    }
}