<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Poller;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Transaction\Transactional;

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
     *          errorChannelName="errorChannel",
     *          maxMessagePerPoll=5,
     *          triggerReferenceName="trigger",
     *          taskExecutorName="taskExecutor",
     *          memoryLimitInMegabytes=100,
     *          requiredInterceptorNames={"some"},
     *          handledMessageLimit=10,
     *          endpointAnnotations={@Transactional("transactionFactory")}
     *     )
     * )
     */
    public function sendMessage(): void
    {
        return;
    }
}