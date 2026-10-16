<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\InMemory;

use Ecotone\Api\ExtensionObject\FinalFailureStrategy;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\PrecedenceChannelInterceptor;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class InMemoryQueueAcknowledgeInterceptorBuilder implements ChannelInterceptorBuilder
{
    public function __construct(private string $relatedChannel, private FinalFailureStrategy $finalFailureStrategy, private bool $isAutoAcked)
    {
    }

    public function relatedChannelName(): string
    {
        return $this->relatedChannel;
    }

    public function getPrecedence(): int
    {
        return PrecedenceChannelInterceptor::DEFAULT_PRECEDENCE;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(InMemoryQueueAcknowledgeInterceptor::class, [
            $this->finalFailureStrategy,
            $this->isAutoAcked,
            new Reference(MessageBuilder::class),
        ]);
    }
}
