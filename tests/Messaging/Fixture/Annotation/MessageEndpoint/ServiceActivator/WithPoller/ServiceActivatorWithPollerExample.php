<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller;

use SimplyCodedSoftware\Messaging\Annotation\EndpointId;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Value;
use SimplyCodedSoftware\Messaging\Annotation\Poller;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class ServiceActivatorWithAllConfigurationDefined
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class ServiceActivatorWithPollerExample
{
    /**
     * @return void
     * @ServiceActivator(
     *     endpointId="test-name",
     *     inputChannelName="inputChannel",
     *     poller=@Poller(
     *          cron="* * * * *",
     *          initialDelayInMilliseconds=2000,
     *          fixedRateInMilliseconds=130,
     *          transactionFactoryReferenceNames={"transaction"},
     *          errorChannelName="errorChannel",
     *          maxMessagePerPoll=5,
     *          triggerReferenceName="trigger",
     *          taskExecutorName="taskExecutor"
     *     )
     * )
     */
    public function sendMessage() : void
    {
        return;
    }
}