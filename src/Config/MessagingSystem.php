<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class Application
 * @package SimplyCodedSoftware\Messaging\Config
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

        $this->initialize();
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
     * @param string $consumerName
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function runConsumerByName(string $consumerName) : void
    {
        foreach ($this->consumers as $consumer) {
            if ($consumer->getConsumerName() === $consumerName) {
                if (!$consumer->isRunningInSeparateThread()) {
                    throw InvalidArgumentException::create("Can't run event driven consumer with name {$consumerName} in separate thread");
                }

                $consumer->start();
            }
        }
    }

    private function initialize() : void
    {
        foreach ($this->consumers as $consumer) {
            if (!$consumer->isRunningInSeparateThread()) {
                $consumer->start();
            }
        }
    }
}