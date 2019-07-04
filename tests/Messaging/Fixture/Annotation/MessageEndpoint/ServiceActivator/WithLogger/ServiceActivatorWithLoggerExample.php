<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithLogger;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogAfter;
use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogBefore;
use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogError;
use SimplyCodedSoftware\Messaging\Handler\Logger\LoggingLevel;

/**
 * Class ServiceActivatorWithLoggerExample
 * @package Fixture\Annotation\MessageEndpoint\ServiceActivator\WithLogger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
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
        return;
    }
}