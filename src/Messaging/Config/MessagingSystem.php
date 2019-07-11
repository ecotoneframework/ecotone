<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Channel\EventDrivenChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Channel\PollableChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycleBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\CombinedGatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;
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
     * @param GatewayReference[]|array $gateways
     * @param ChannelResolver $channelResolver
     * @throws MessagingException
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

    private function initialize(): void
    {
        foreach ($this->consumers as $consumer) {
            if (!$consumer->isRunningInSeparateThread()) {
                $consumer->run();
            }
        }
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param MessageChannelBuilder[] $messageChannelBuilders
     * @param MessageChannelBuilder[] $messageChannelInterceptors
     * @param GatewayBuilder[] $gatewayBuilders
     * @param ConsumerLifecycleBuilder[] $consumerFactories
     * @param PollingMetadata[] $pollingMetadataConfigurations
     * @param MessageHandlerBuilder[] $messageHandlerBuilders
     * @param ChannelAdapterConsumerBuilder[] $channelAdapterConsumerBuilders
     * @return MessagingSystem
     * @throws MessagingException
     * @throws NoConsumerFactoryForBuilderException
     */
    public static function createFrom(
        ReferenceSearchService $referenceSearchService,
        array $messageChannelBuilders, array $messageChannelInterceptors,
        array $gatewayBuilders, array $consumerFactories,
        array $pollingMetadataConfigurations, array $messageHandlerBuilders, array $channelAdapterConsumerBuilders
    )
    {
        $channelResolver = self::createChannelResolver($messageChannelInterceptors, $messageChannelBuilders, $referenceSearchService);

        $gateways = self::configureGateways($gatewayBuilders, $referenceSearchService, $channelResolver);

        $consumerEndpointFactory = new ConsumerEndpointFactory($channelResolver, $referenceSearchService, $consumerFactories, $pollingMetadataConfigurations);
        $consumers = [];

        foreach ($messageHandlerBuilders as $messageHandlerBuilder) {
            $consumers[] = $consumerEndpointFactory->createForMessageHandler($messageHandlerBuilder, $messageChannelBuilders);
        }

        foreach ($channelAdapterConsumerBuilders as $channelAdapter) {
            $consumers[] = $channelAdapter->build($channelResolver, $referenceSearchService, array_key_exists($channelAdapter->getEndpointId(), $pollingMetadataConfigurations) ? $pollingMetadataConfigurations[$channelAdapter->getEndpointId()] : PollingMetadata::create($channelAdapter->getEndpointId()));
        }

        return MessagingSystem::create($consumers, $gateways, $channelResolver);
    }

    /**
     * @param ChannelInterceptorBuilder[] $channelInterceptorBuilders
     * @param MessageChannelBuilder[] $channelBuilders
     * @param ReferenceSearchService $referenceSearchService
     * @return ChannelResolver
     * @throws MessagingException
     */
    private static function createChannelResolver(array $channelInterceptorBuilders, array $channelBuilders, ReferenceSearchService $referenceSearchService): ChannelResolver
    {
        $channels = [];
        foreach ($channelBuilders as $channelsBuilder) {
            $messageChannel = $channelsBuilder->build($referenceSearchService);
            $interceptorsForChannel = [];
            foreach ($channelInterceptorBuilders as $channelName => $interceptors) {
                $regexChannel = str_replace("*", ".*", $channelName);
                if (preg_match("#^{$regexChannel}$#", $channelsBuilder->getMessageChannelName())) {
                    $interceptorsForChannel = array_merge($interceptorsForChannel, array_map(function(ChannelInterceptorBuilder $channelInterceptorBuilder) use ($referenceSearchService) {
                        return $channelInterceptorBuilder->build($referenceSearchService);
                    }, $interceptors));
                }
            }

            if ($messageChannel instanceof PollableChannel && $interceptorsForChannel) {
                $messageChannel = new PollableChannelInterceptorAdapter($messageChannel, $interceptorsForChannel);
            } else if ($interceptorsForChannel) {
                $messageChannel = new EventDrivenChannelInterceptorAdapter($messageChannel, $interceptorsForChannel);
            }

            $channels[] = NamedMessageChannel::create($channelsBuilder->getMessageChannelName(), $messageChannel);
        }

        return InMemoryChannelResolver::create($channels);
    }

    /**
     * @param array $preparedGateways
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     * @return array
     * @throws MessagingException
     */
    private static function configureGateways(array $preparedGateways, ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver): array
    {
        $gateways = [];
        foreach ($preparedGateways as $referenceName => $preparedGatewaysForReference) {
            if (count($preparedGatewaysForReference) === 1) {
                $gateways[] = GatewayReference::createWith(
                    $preparedGatewaysForReference[0]->getReferenceName(),
                    $preparedGatewaysForReference[0]->build($referenceSearchService, $channelResolver)
                );
            } else {
                $gateways[] =
                    GatewayReference::createWith(
                        $referenceName,
                        CombinedGatewayBuilder::create(
                            $preparedGatewaysForReference[0]->getInterfaceName(), $preparedGatewaysForReference
                        )->build($referenceSearchService, $channelResolver)
                    );
            }
        }
        return $gateways;
    }

    /**
     * @param iterable $consumers
     * @param GatewayReference[]|array $gateways
     * @param ChannelResolver $channelResolver
     * @return MessagingSystem
     * @throws MessagingException
     * @internal
     */
    public static function create(iterable $consumers, array $gateways, ChannelResolver $channelResolver): self
    {
        return new self($consumers, $gateways, $channelResolver);
    }

    /**
     * @param string $consumerName
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function runSeparatelyRunningConsumerBy(string $consumerName): void
    {
        foreach ($this->consumers as $consumer) {
            if ($consumer->getConsumerName() === $consumerName) {
                Assert::isTrue($consumer->isRunningInSeparateThread(), "Can't run event driven consumer with name {$consumerName} in separate thread");

                $consumer->run();
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
}