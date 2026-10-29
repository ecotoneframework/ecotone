<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\Assert;

/**
 * Class InMemoryChannelResolver
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class InMemoryChannelResolver implements ChannelResolver
{
    private ?array $resolvableChannels = null;
    private ?ChannelResolver $externalChannelResolver = null;

    /**
     * InMemoryChannelResolver constructor.
     * @param array|NamedMessageChannel[] $resolvableChannels
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(array $resolvableChannels)
    {
        $this->initialize($resolvableChannels);
    }

    /**
     * @param array|NamedMessageChannel[] $namedMessageChannels
     * @return InMemoryChannelResolver
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function create(array $namedMessageChannels): self
    {
        return new self($namedMessageChannels);
    }

    /**
     * @param array|string[] $resolvableChannelsArray
     * @return InMemoryChannelResolver
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createFromAssociativeArray(array $resolvableChannelsArray): self
    {
        $resolvableChannels = [];

        foreach ($resolvableChannelsArray as $channelName => $messageChannel) {
            Assert::isSubclassOf($messageChannel, MessageChannel::class, 'Expected Message Channel got ' . get_class($messageChannel));

            $resolvableChannels[] = NamedMessageChannel::create($channelName, $messageChannel);
        }

        return new self($resolvableChannels);
    }

    /**
     * @param ChannelResolver $channelResolver
     * @param array $associativeAdditionalChannels
     * @return InMemoryChannelResolver
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWithChannelResolver(ChannelResolver $channelResolver, array $associativeAdditionalChannels): self
    {
        $self = self::createFromAssociativeArray($associativeAdditionalChannels);
        $self->withExternalChannelResolver($channelResolver);

        return $self;
    }

    /**
     * @return InMemoryChannelResolver
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string|MessageChannel $channelName): MessageChannel
    {
        if ($channelName instanceof MessageChannel) {
            return $channelName;
        }

        if (array_key_exists($channelName, $this->resolvableChannels)) {
            return $this->resolvableChannels[$channelName];
        }

        if ($this->externalChannelResolver) {
            return $this->externalChannelResolver->resolve($channelName);
        }

        throw DestinationResolutionException::create("Channel with name {$channelName} can't be resolved");
    }

    /**
     * @inheritDoc
     */
    public function hasChannelWithName(string $channelName): bool
    {
        if (array_key_exists($channelName, $this->resolvableChannels)) {
            return true;
        }

        if ($this->externalChannelResolver) {
            return $this->externalChannelResolver->hasChannelWithName($channelName);
        }

        return false;
    }

    /**
     * @param array|NamedMessageChannel[] $namedMessageChannels
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function initialize($namedMessageChannels): void
    {
        Assert::allInstanceOfType($namedMessageChannels, NamedMessageChannel::class);

        /** @var NamedMessageChannel[] $namedMessageChannels */
        $namedMessageChannels = array_merge($namedMessageChannels, [NamedMessageChannel::create(NullableMessageChannel::CHANNEL_NAME, NullableMessageChannel::create())]);

        foreach ($namedMessageChannels as $namedMessageChannel) {
            $this->resolvableChannels[$namedMessageChannel->getName()] = $namedMessageChannel->getMessageChannel();
        }
    }

    /**
     * @param ChannelResolver $channelResolver
     */
    private function withExternalChannelResolver(ChannelResolver $channelResolver): void
    {
        $this->externalChannelResolver = $channelResolver;
    }
}
