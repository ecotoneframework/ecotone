<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\MessagePoller\PollableChannelPollerAdapter;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;

/**
 * This class is stateless Service which creates Message Consumers for Message Handlers.
 * It should not hold any state, as it will be reused for different endpoints.
 */
/**
 * licence Apache-2.0
 */
class PollingConsumerBuilder extends InterceptedPollingConsumerBuilder
{
    protected function compileMessagePoller(MessagingContainerBuilder $builder, MessageHandlerBuilder $messageHandlerBuilder): Definition
    {
        return new Definition(PollableChannelPollerAdapter::class, [
            $messageHandlerBuilder->getInputMessageChannelName(),
            new ChannelReference($messageHandlerBuilder->getInputMessageChannelName()),
        ]);
    }
}
