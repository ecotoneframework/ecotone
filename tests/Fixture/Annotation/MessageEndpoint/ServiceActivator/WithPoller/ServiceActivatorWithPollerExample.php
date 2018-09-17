<?php
declare(strict_types=1);

namespace Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointId;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Value;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Poller;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class ServiceActivatorWithAllConfigurationDefined
 * @package Fixture\Annotation\MessageEndpoint
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