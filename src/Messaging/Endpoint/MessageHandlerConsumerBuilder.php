<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;

/**
 * Interface PollableFactory
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageHandlerConsumerBuilder
{
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool;

    public function isPollingConsumer(): bool;

    public function registerConsumer(MessagingContainerBuilder $builder, MessageHandlerBuilder $messageHandlerBuilder): void;
}
