<?php

namespace Messaging\Config;

use Messaging\Endpoint\ConsumerEndpointFactory;
use Messaging\Endpoint\ConsumerLifecycle;
use Messaging\Support\Assert;

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
     * Application constructor.
     * @param iterable|ConsumerLifecycle[] $consumers
     */
    private function __construct(iterable $consumers)
    {
        Assert::allInstanceOfType($consumers, ConsumerLifecycle::class);
        $this->consumers = $consumers;
    }

    /**
     * @param iterable $consumers
     * @return MessagingSystem
     */
    public static function create(iterable $consumers) : self
    {
        return new self($consumers);
    }

    public function runEventDrivenConsumers() : void
    {
        foreach ($this->consumers as $consumer) {
            if (!$consumer->isPollable()) {
                $consumer->start();
            }
        }
    }

    public function runPollableByName(string $consumerName) : void
    {
        foreach ($this->consumers as $consumer) {
            if ($consumer->getConsumerName() === $consumerName) {
                $consumer->start();
            }
        }
    }

    public function checkConfiguration() : void
    {

    }
}