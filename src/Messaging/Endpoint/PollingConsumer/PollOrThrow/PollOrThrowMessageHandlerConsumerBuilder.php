<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer\PollOrThrow;

use Ecotone\Messaging\Channel\DynamicChannel\DynamicMessageChannelBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;

/**
 * Class PollOrThrowPollableFactory
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PollOrThrowMessageHandlerConsumerBuilder implements MessageHandlerConsumerBuilder
{
    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        if ($relatedMessageChannel instanceof DynamicMessageChannelBuilder && ! $relatedMessageChannel->hasReceiveStrategy()) {
            return false;
        }

        return $relatedMessageChannel->isPollable();
    }

    public function isPollingConsumer(): bool
    {
        return true;
    }

    public function registerConsumer(MessagingContainerBuilder $builder, MessageHandlerBuilder $messageHandlerBuilder): void
    {
        $messageHandlerReference = $messageHandlerBuilder->compile($builder);
        $consumerRunner = new Definition(PollOrThrowExceptionConsumer::class, [
            Reference::toChannel($messageHandlerBuilder->getInputMessageChannelName()),
            $messageHandlerReference,
        ], 'create');
        $builder->registerPollingEndpoint($messageHandlerBuilder->getEndpointId(), $consumerRunner);
    }
}
