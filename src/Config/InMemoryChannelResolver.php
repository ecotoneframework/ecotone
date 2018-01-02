<?php

namespace Messaging\Config;

use Messaging\Handler\ChannelResolver;
use Messaging\Handler\DestinationResolutionException;
use Messaging\MessageChannel;
use Messaging\Support\Assert;

/**
 * Class InMemoryChannelResolver
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryChannelResolver implements ChannelResolver
{
    /**
     * @var array|NamedMessageChannel[]
     */
    private $resolvableChannels;

    /**
     * InMemoryChannelResolver constructor.
     * @param array|NamedMessageChannel[] $resolvableChannels
     */
    private function __construct(array $resolvableChannels)
    {
        $this->initialize($resolvableChannels);
    }

    /**
     * @param array|NamedMessageChannel[] $namedMessageChannels
     * @return InMemoryChannelResolver
     */
    public static function create(array $namedMessageChannels) : self
    {
        return new self($namedMessageChannels);
    }

    /**
     * @param array|string[] $resolvableChannelsArray
     * @return InMemoryChannelResolver
     */
    public static function createFromAssociativeArray(array $resolvableChannelsArray) : self
    {
        $resolvableChannels = [];

        foreach ($resolvableChannelsArray as $channelName => $messageChannel) {
            Assert::isSubclassOf($messageChannel, MessageChannel::class, "Exepected Message Channel got " . get_class($messageChannel));

            $resolvableChannels[] = NamedMessageChannel::create($channelName, $messageChannel);
        }

        return new self($resolvableChannels);
    }

    /**
     * @return InMemoryChannelResolver
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

        throw DestinationResolutionException::create("Channel with name {$channelName} can't be resolved");
    }

    /**
     * @param array|NamedMessageChannel[] $namedMessageChannels
     */
    private function initialize($namedMessageChannels) : void
    {
        Assert::allInstanceOfType($namedMessageChannels, NamedMessageChannel::class);

        $this->resolvableChannels = $namedMessageChannels;
    }
}