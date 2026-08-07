<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\AsyncPublishing;

use Ecotone\Messaging\Channel\AsyncPublishing\AsyncPublishingRegistry;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;

/**
 * licence Apache-2.0
 */
final class InMemoryAsyncPublishingChannelBuilder implements MessageChannelBuilder
{
    private function __construct(private string $channelName)
    {
    }

    public static function create(string $channelName): self
    {
        return new self($channelName);
    }

    public function getMessageChannelName(): string
    {
        return $this->channelName;
    }

    public function isPollable(): bool
    {
        return true;
    }

    public function isStreamingChannel(): bool
    {
        return false;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(InMemoryAsyncPublishingChannel::class, [
            $this->channelName,
            new Reference(AsyncPublishingRegistry::class),
            new Reference(OperationsLog::class),
        ]);
    }
}
