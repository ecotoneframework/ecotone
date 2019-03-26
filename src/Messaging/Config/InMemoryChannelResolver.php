<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\NullableMessageChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class InMemoryChannelResolver
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryChannelResolver implements ChannelResolver
{
    /**
     * @var array|NamedMessageChannel[]
     */
    private $resolvableChannels;
    /**
     * @var ChannelResolver
     */
    private $externalChannelResolver;

    /**
     * InMemoryChannelResolver constructor.
     * @param array|NamedMessageChannel[] $resolvableChannels
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(array $resolvableChannels)
    {
        $this->initialize($resolvableChannels);
    }

    /**
     * @param array|NamedMessageChannel[] $namedMessageChannels
     * @return InMemoryChannelResolver
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function create(array $namedMessageChannels) : self
    {
        return new self($namedMessageChannels);
    }

    /**
     * @param array|string[] $resolvableChannelsArray
     * @return InMemoryChannelResolver
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createFromAssociativeArray(array $resolvableChannelsArray) : self
    {
        $resolvableChannels = [];

        foreach ($resolvableChannelsArray as $channelName => $messageChannel) {
            Assert::isSubclassOf($messageChannel, MessageChannel::class, "Expected Message Channel got " . get_class($messageChannel));

            $resolvableChannels[] = NamedMessageChannel::create($channelName, $messageChannel);
        }

        return new self($resolvableChannels);
    }

    /**
     * @param ChannelResolver $channelResolver
     * @param array $associativeAdditionalChannels
     * @return InMemoryChannelResolver
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWithChannelResolver(ChannelResolver $channelResolver, array $associativeAdditionalChannels) : self
    {
        $self = self::createFromAssociativeArray($associativeAdditionalChannels);
        $self->withExternalChannelResolver($channelResolver);

        return $self;
    }

    /**
     * @return InMemoryChannelResolver
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function resolve($channelName): MessageChannel
    {
        if ($channelName instanceof MessageChannel) {
            return $channelName;
        }

        foreach ($this->resolvableChannels as $resolvableChannel) {
            if ($resolvableChannel->hasName($channelName)) {
                return $resolvableChannel->getMessageChannel();
            }
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
        foreach ($this->resolvableChannels as $resolvableChannel) {
            if ($resolvableChannel->hasName($channelName)) {
                return true;
            }
        }

        if ($this->externalChannelResolver) {
            return $this->externalChannelResolver->hasChannelWithName($channelName);
        }

        return false;
    }

    /**
     * @param array|NamedMessageChannel[] $namedMessageChannels
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize($namedMessageChannels) : void
    {
        Assert::allInstanceOfType($namedMessageChannels, NamedMessageChannel::class);

        $this->resolvableChannels = array_merge($namedMessageChannels, [NamedMessageChannel::create(NullableMessageChannel::CHANNEL_NAME, NullableMessageChannel::create())]);
    }

    /**
     * @param ChannelResolver $channelResolver
     */
    private function withExternalChannelResolver(ChannelResolver $channelResolver) : void
    {
        $this->externalChannelResolver = $channelResolver;
    }
}