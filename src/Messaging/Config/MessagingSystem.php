<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class Application
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystem implements ConfiguredMessagingSystem
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
     * @var GatewayReference[]
     */
    private $gatewayReferences;

    /**
     * Application constructor.
     * @param iterable|ConsumerLifecycle[] $consumers
     * @param object[]|array $gateways
     * @param ChannelResolver $channelResolver
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(iterable $consumers, array $gateways, ChannelResolver $channelResolver)
    {
        Assert::allInstanceOfType($consumers, ConsumerLifecycle::class);
        Assert::allInstanceOfType($gateways, GatewayReference::class);

        $this->consumers = $consumers;
        $this->channelResolver = $channelResolver;
        $this->gatewayReferences = $gateways;

        $this->initialize();
    }

    /**
     * @param iterable $consumers
     * @param object[]|array $gateways
     * @param ChannelResolver $channelResolver
     * @return MessagingSystem
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @internal
     */
    public static function create(iterable $consumers, array $gateways, ChannelResolver $channelResolver) : self
    {
        return new self($consumers, $gateways, $channelResolver);
    }

    /**
     * @param string $consumerName
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function runSeparatelyRunningConsumerBy(string $consumerName) : void
    {
        foreach ($this->consumers as $consumer) {
            if ($consumer->getConsumerName() === $consumerName) {
                Assert::isTrue($consumer->isRunningInSeparateThread(), "Can't run event driven consumer with name {$consumerName} in separate thread");

                $consumer->start();
                return;
            }
        }

        throw InvalidArgumentException::create("There is no pollable consumer with name {$consumerName} to run");
    }

    /**
     * @inheritDoc
     */
    public function getGatewayByName(string $gatewayReferenceName)
    {
        foreach ($this->gatewayReferences as $gatewayReference) {
            if ($gatewayReference->hasReferenceName($gatewayReferenceName)) {
                return $gatewayReference->getGateway();
            }
        }

        throw InvalidArgumentException::create("Gateway with reference {$gatewayReferenceName} does not exists");
    }

    /**
     * @inheritDoc
     */
    public function getGatewayList(): iterable
    {
        return $this->gatewayReferences;
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelByName(string $channelName): MessageChannel
    {
        return $this->channelResolver->resolve($channelName);
    }

    /**
     * @inheritDoc
     */
    public function getListOfSeparatelyRunningConsumers(): array
    {
        $list = [];

        foreach ($this->consumers as $consumer) {
            if ($consumer->isRunningInSeparateThread()) {
                $list[] = $consumer->getConsumerName();
            }
        }

        return $list;
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