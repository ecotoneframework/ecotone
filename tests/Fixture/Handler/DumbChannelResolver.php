<?php

namespace Fixture\Handler;

use Messaging\Handler\ChannelResolver;
use Messaging\Handler\DestinationResolutionException;
use Messaging\MessageChannel;

/**
 * Class DumbChannelResolver
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbChannelResolver implements ChannelResolver
{
    /**
     * @var iterable
     */
    private $channels;

    /**
     * DumbChannelResolver constructor.
     * @param iterable $channels
     */
    private function __construct(iterable $channels)
    {
        $this->channels = $channels;
    }

    public static function create($channels) : self
    {
        return new self($channels);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $channelName): MessageChannel
    {
        if (array_key_exists($channelName, $this->channels)) {
            return $this->channels[$channelName];
        }

        throw DestinationResolutionException::create("Message channel with name {$channelName} does not exists");
    }
}