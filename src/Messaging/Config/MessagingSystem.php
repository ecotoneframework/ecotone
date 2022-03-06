<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\EventDrivenChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\PollableChannelInterceptorAdapter;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\CombinedGatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;

/**
 * Class Application
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystem implements ConfiguredMessagingSystem
{
    const POLLING_CONSUMER_BUILDER = "builder";
    const POLLING_CONSUMER_HANDLER = "handler";

    /**
     * Application constructor.
     * @param ConsumerLifecycle[] $eventDrivenConsumers
     * @param MessageHandlerBuilder[] $pollingConsumerBuilders
     * @param InboundChannelAdapterBuilder[] $inboundChannelAdapterBuilders
     * @param GatewayReference[]|array $gatewayReferences
     * @param NonProxyCombinedGateway[]|array $nonProxyCombinedGateways
     * @param ConsoleCommandConfiguration[] $consoleCommands
     * @param ChannelResolver $channelResolver
     * @param PollingMetadata[] $pollingMetadataConfigurations
     */
    private function __construct(
        private array                  $eventDrivenConsumers,
        private array                  $pollingConsumerBuilders,
        private array                  $inboundChannelAdapterBuilders,
        private array                  $gatewayReferences,
        private array                  $nonProxyCombinedGateways,
        private ChannelResolver        $channelResolver,
        private ReferenceSearchService $referenceSearchService,
        private array                  $pollingMetadataConfigurations,
        private array                  $consoleCommands
    )
    {
        foreach ($eventDrivenConsumers as $consumer) {
            $consumer->run();
        }
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param MessageChannelBuilder[] $messageChannelBuilders
     * @param ChannelInterceptorBuilder[] $messageChannelInterceptors
     * @param GatewayProxyBuilder[][] $gatewayBuilders
     * @param MessageHandlerConsumerBuilder[] $messageHandlerConsumerFactories
     * @param PollingMetadata[] $pollingMetadataConfigurations
     * @param MessageHandlerBuilder[] $messageHandlerBuilders
     * @param ChannelAdapterConsumerBuilder[] $channelAdapterConsumerBuilders
     * @param bool $isLazyConfiguration
     * @param ConsoleCommandConfiguration[] $consoleCommands
     * @throws MessagingException
     */
    public static function createFrom(
        ReferenceSearchService $referenceSearchService,
        array                  $messageChannelBuilders, array $messageChannelInterceptors,
        array                  $gatewayBuilders, array $messageHandlerConsumerFactories,
        array                  $pollingMetadataConfigurations, array $messageHandlerBuilders, array $channelAdapterConsumerBuilders,
        bool                   $isLazyConfiguration,
        array                  $consoleCommands
    ): MessagingSystem
    {
        $channelResolver = self::createChannelResolver($messageChannelInterceptors, $messageChannelBuilders, $referenceSearchService);

        list($gateways, $nonProxyGateways) = self::configureGateways($gatewayBuilders, $referenceSearchService, $channelResolver, $isLazyConfiguration);

        $gatewayReferences = [];
        foreach ($gateways as $gateway) {
            $gatewayReferences[$gateway->getReferenceName()] = $gateway->getGateway();
            $referenceSearchService->registerReferencedObject($gateway->getReferenceName(), $gatewayReferences[$gateway->getReferenceName()]);
        }
        $referenceSearchService->registerReferencedObject(ChannelResolver::class, $channelResolver);

        $eventDrivenConsumers = [];
        $pollingConsumerBuilders = [];
        foreach ($messageHandlerBuilders as $messageHandlerBuilder) {
            Assert::keyExists($messageChannelBuilders, $messageHandlerBuilder->getInputMessageChannelName(), "Missing channel with name {$messageHandlerBuilder->getInputMessageChannelName()} for {$messageHandlerBuilder}");
            $messageChannel = $messageChannelBuilders[$messageHandlerBuilder->getInputMessageChannelName()];
            foreach ($messageHandlerConsumerFactories as $messageHandlerConsumerBuilder) {
                if ($messageHandlerConsumerBuilder->isSupporting($messageHandlerBuilder, $messageChannel)) {
                    if ($messageHandlerConsumerBuilder->isPollingConsumer()) {
                        $pollingConsumerBuilders[$messageHandlerBuilder->getEndpointId()] = [
                            self::POLLING_CONSUMER_BUILDER => $messageHandlerConsumerBuilder,
                            self::POLLING_CONSUMER_HANDLER => $messageHandlerBuilder
                        ];
                    } else {
                        $eventDrivenConsumers[] = $messageHandlerConsumerBuilder->build($channelResolver, $referenceSearchService, $messageHandlerBuilder, self::getPollingMetadata($messageHandlerBuilder->getEndpointId(), $pollingMetadataConfigurations));
                    }
                }
            }
        }

        $inboundChannelAdapterBuilders = [];
        foreach ($channelAdapterConsumerBuilders as $channelAdapter) {
            $endpointId = $channelAdapter->getEndpointId();
            $inboundChannelAdapterBuilders[$endpointId] = $channelAdapter;
        }

        return new self($eventDrivenConsumers, $pollingConsumerBuilders, $inboundChannelAdapterBuilders, $gateways, $nonProxyGateways, $channelResolver, $referenceSearchService, $pollingMetadataConfigurations, $consoleCommands);
    }

    /**
     * @param ChannelInterceptorBuilder[] $channelInterceptorBuilders
     * @param MessageChannelBuilder[] $channelBuilders
     * @param ReferenceSearchService $referenceSearchService
     * @throws MessagingException
     */
    private static function createChannelResolver(array $channelInterceptorBuilders, array $channelBuilders, ReferenceSearchService $referenceSearchService): InMemoryChannelResolver
    {
        $channels = [];
        foreach ($channelBuilders as $channelsBuilder) {
            $messageChannel = $channelsBuilder->build($referenceSearchService);
            $interceptorsForChannel = [];
            foreach ($channelInterceptorBuilders as $channelName => $interceptors) {
                $regexChannel = str_replace("*", ".*", $channelName);
                if (preg_match("#^{$regexChannel}$#", $channelsBuilder->getMessageChannelName())) {
                    $interceptorsForChannel = array_merge($interceptorsForChannel, array_map(function (ChannelInterceptorBuilder $channelInterceptorBuilder) use ($referenceSearchService) {
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
     * @param GatewayProxyBuilder[][] $preparedGateways
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     * @param bool $isLazyConfiguration
     * @return GatewayReference[]
     * @throws MessagingException
     */
    private static function configureGateways(array $preparedGateways, ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver, bool $isLazyConfiguration): array
    {
        $gateways = [];
        $nonProxyCombinedGateways = [];
        foreach ($preparedGateways as $referenceName => $preparedGatewaysForReference) {
            $referenceName = $preparedGatewaysForReference[0]->getReferenceName();

            if (count($preparedGatewaysForReference) === 1) {
                $gatewayProxyBuilder = $preparedGatewaysForReference[0]
                    ->withLazyBuild($isLazyConfiguration);
                $nonProxyCombinedGateways[$referenceName] = NonProxyCombinedGateway::createWith($referenceName, [$gatewayProxyBuilder->getRelatedMethodName() => $gatewayProxyBuilder->buildWithoutProxyObject($referenceSearchService, $channelResolver)]);
                $gateways[$referenceName] = GatewayReference::createWith(
                    $referenceName,
                    $gatewayProxyBuilder->build($referenceSearchService, $channelResolver)
                );
            } else {
                $nonProxyCombinedGatewaysMethods = [];
                foreach ($preparedGatewaysForReference as $proxyBuilder) {
                    $nonProxyCombinedGatewaysMethods[$proxyBuilder->getRelatedMethodName()] =
                        $proxyBuilder
                            ->withLazyBuild($isLazyConfiguration)
                            ->buildWithoutProxyObject($referenceSearchService, $channelResolver);
                }

                $nonProxyCombinedGateways[$referenceName] = NonProxyCombinedGateway::createWith($referenceName, $nonProxyCombinedGatewaysMethods);
                $gateways[$referenceName] =
                    GatewayReference::createWith(
                        $referenceName,
                        CombinedGatewayBuilder::create($preparedGatewaysForReference[0]->getInterfaceName(), $nonProxyCombinedGatewaysMethods)
                            ->build($referenceSearchService, $channelResolver)
                    );
            }
        }
        return [$gateways, $nonProxyCombinedGateways];
    }

    private static function getPollingMetadata(string $endpointId, array $pollingMetadataConfigurations): PollingMetadata
    {
        return array_key_exists($endpointId, $pollingMetadataConfigurations) ? $pollingMetadataConfigurations[$endpointId] : PollingMetadata::create($endpointId);
    }

    public function run(string $name, ?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        $pollingMetadata = self::getPollingMetadata($name, $this->pollingMetadataConfigurations)
            ->applyExecutionPollingMetadata($executionPollingMetadata);

        if (array_key_exists($name, $this->pollingConsumerBuilders)) {
            /** @var MessageHandlerConsumerBuilder $consumerBuilder */
            $consumerBuilder = $this->pollingConsumerBuilders[$name][self::POLLING_CONSUMER_BUILDER];

            $consumerBuilder->build(
                $this->channelResolver,
                $this->referenceSearchService,
                $this->pollingConsumerBuilders[$name][self::POLLING_CONSUMER_HANDLER],
                $pollingMetadata
            )->run();
        } else if (array_key_exists($name, $this->inboundChannelAdapterBuilders)) {
            $this->inboundChannelAdapterBuilders[$name]->build(
                $this->channelResolver,
                $this->referenceSearchService,
                $pollingMetadata
            )->run();
        } else {
            throw InvalidArgumentException::create("Can't run `{$name}` as it does not exists. Please verify, if the name is correct using `ecotone:list`.");
        }
    }

    public function getServiceFromContainer(string $referenceName): object
    {
        Assert::isTrue($this->referenceSearchService->has($referenceName), "Service with reference {$referenceName} does not exists");

        return $this->referenceSearchService->get($referenceName);
    }

    /**
     * @inheritDoc
     */
    public function getGatewayByName(string $gatewayReferenceName): object
    {
        foreach ($this->gatewayReferences as $gatewayReference) {
            if ($gatewayReference->hasReferenceName($gatewayReferenceName)) {
                return $gatewayReference->getGateway();
            }
        }

        throw InvalidArgumentException::create("Gateway with reference {$gatewayReferenceName} does not exists");
    }

    public function getNonProxyGatewayByName(string $gatewayReferenceName): NonProxyCombinedGateway
    {
        Assert::keyExists($this->nonProxyCombinedGateways, $gatewayReferenceName, "Gateway with reference {$gatewayReferenceName} does not exists");

        return $this->nonProxyCombinedGateways[$gatewayReferenceName];
    }

    public function runConsoleCommand(string $commandName, array $parameters): mixed
    {
        $consoleCommandConfiguration = null;
        foreach ($this->consoleCommands as $consoleCommand) {
            if ($consoleCommand->getName() === $commandName) {
                $consoleCommandConfiguration = $consoleCommand;
            }
        }
        Assert::notNull($consoleCommandConfiguration, "Trying to run not existing console command {$commandName}");
        /** @var MessagingEntrypoint $gateway */
        $gateway = $this->getGatewayByName(MessagingEntrypoint::class);

        $arguments = [];

        foreach ($parameters as $argumentName => $value) {
            if (!$this->hasParameterWithGivenName($consoleCommandConfiguration, $argumentName)) {
                continue;
            }

            $arguments[$consoleCommandConfiguration->getHeaderNameForParameterName($argumentName)] = $value;
        }
        foreach ($consoleCommandConfiguration->getParameters() as $commandParameter) {
            if (!array_key_exists($consoleCommandConfiguration->getHeaderNameForParameterName($commandParameter->getName()), $arguments)) {
                if (!$commandParameter->hasDefaultValue()) {
                    throw InvalidArgumentException::create("Missing argument with name {$commandParameter->getName()} for console command {$commandName}");
                }

                $arguments[$consoleCommandConfiguration->getHeaderNameForParameterName($commandParameter->getName())] = $commandParameter->getDefaultValue();
            }
        }

        return $gateway->sendWithHeaders([], $arguments, $consoleCommandConfiguration->getChannelName());
    }

    /**
     * @inheritDoc
     */
    public function getGatewayList(): iterable
    {
        return $this->gatewayReferences;
    }

    public function getCommandBus(): CommandBus
    {
        return $this->getGatewayByName(CommandBus::class);
    }

    public function getQueryBus(): QueryBus
    {
        return $this->getGatewayByName(QueryBus::class);
    }

    public function getEventBus(): EventBus
    {
        return $this->getGatewayByName(EventBus::class);
    }

    public function getDistributedBus(): DistributedBus
    {
        return $this->getGatewayByName(DistributedBus::class);
    }

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher
    {
        return $this->getGatewayByName($referenceName);
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
    public function list(): array
    {
        return array_merge(array_keys($this->pollingConsumerBuilders), array_keys($this->inboundChannelAdapterBuilders));
    }

    /**
     * @param ConsoleCommandConfiguration|null $consoleCommandConfiguration
     * @param int|string $argumentName
     * @return bool
     */
    private function hasParameterWithGivenName(?ConsoleCommandConfiguration $consoleCommandConfiguration, int|string $argumentName): bool
    {
        foreach ($consoleCommandConfiguration->getParameters() as $commandParameter) {
            if ($commandParameter->getName() === $argumentName) {
                return true;
            }
        }

        return false;
    }
}