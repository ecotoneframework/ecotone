<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Messaging\Annotation\RetryTemplate;
use Ecotone\Messaging\Annotation\ServiceActivator;

/**
 * Class ServiceActivatorWithAllConfigurationDefined
 * @package Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class ServiceActivatorWithPollerExample
{
    /**
     * @return void
     * @ServiceActivator(
     *     endpointId="test-name",
     *     inputChannelName="inputChannel"
     * )
     */
    public function sendMessage(): void
    {
        return;
    }
}