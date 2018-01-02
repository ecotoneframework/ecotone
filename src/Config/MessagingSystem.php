<?php

namespace Messaging\Config;

use Messaging\Endpoint\ConsumerEndpointFactory;
use Messaging\Endpoint\ConsumerLifecycle;
use Messaging\Handler\ChannelResolver;
use Messaging\Handler\MessageHandlerBuilder;
use Messaging\Support\Assert;
use Messaging\Support\InvalidArgumentException;

/**
 * Class Application
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystem
{
    /**
     * @var iterable|ConsumerLifecycle[]
     */
    private $consumers;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;

    /**
     * Application constructor.
     * @param iterable|ConsumerLifecycle[] $consumers
     * @param ChannelResolver $channelResolver
     */
    private function __construct(iterable $consumers, ChannelResolver $channelResolver)
    {
        Assert::allInstanceOfType($consumers, ConsumerLifecycle::class);
        $this->consumers = $consumers;
        $this->channelResolver = $channelResolver;
    }

    /**
     * @param iterable $consumers
     * @param ChannelResolver $channelResolver
     * @return MessagingSystem
     * @internal
     */
    public static function create(iterable $consumers, ChannelResolver $channelResolver) : self
    {
        return new self($consumers, $channelResolver);
    }

    /**
     * Run al event driven
     */
    public function runEventDrivenConsumers() : void
    {
        foreach ($this->consumers as $consumer) {
            if (!$consumer->isPollable()) {
                $consumer->start();
            }
        }
    }

    public function stopEventDrivenConsumers() : void
    {
        foreach ($this->consumers as $consumer) {
            if (!$consumer->isPollable()) {
                $consumer->stop();
            }
        }
    }

    /**
     * @param string $consumerName
     * @throws \Messaging\MessagingException
     */
    public function runPollableByName(string $consumerName) : void
    {
        foreach ($this->consumers as $consumer) {
            if ($consumer->getConsumerName() === $consumerName) {
                if (!$consumer->isPollable()) {
                    throw InvalidArgumentException::create("Can't run event driven consumer with name {$consumerName} as pollable");
                }
                $consumer->start();
            }
        }
    }
}