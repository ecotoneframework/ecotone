<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ErrorHandler\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\ErrorHandler\RetryTemplateBuilder;
use Ecotone\Messaging\MessagingException;

/**
 * Class ChannelConfiguration
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class ChannelConfiguration
{
    const ERROR_CHANNEL = "errorChannel";

    /**
     * @Extension()
     */
    public function registerAsyncChannel() : array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel("orders"),
            PollingMetadata::create("orders")
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName(self::ERROR_CHANNEL)
        ];
    }
}