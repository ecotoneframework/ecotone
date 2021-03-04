<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\EventDrivenChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\PollableChannelInterceptorAdapter;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\ConsumerEndpointFactory;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\ConsumerLifecycleBuilder;
use Ecotone\Messaging\Endpoint\NoConsumerFactoryForBuilderException;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\ConsoleCommandRunner;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\CombinedGatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class Application
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystem implements ConfiguredMessagingSystem
{
    private iterable $consumers;
    private \Ecotone\Messaging\Handler\ChannelResolver $channelResolver;
    /**
     * @var GatewayReference[]
     */
    private array $gatewayReferences;
    /**
     * @var NonProxyCombinedGateway[]
     */
    private array $nonProxyCombinedGateways;
    /**
     * @var ConsoleCommandConfiguration[]
     */
    private array $consoleCommands;

    /**
     * Application constructor.
     * @param iterable|ConsumerLifecycle[] $consumers
     * @param GatewayReference[]|array $gateways
     * @param NonProxyCombinedGateway[]|array $nonProxyCombinedGateways
     * @param ConsoleCommandConfiguration[] $consoleCommands
     * @param ChannelResolver $channelResolver
     * @throws MessagingException
     */
    private function __construct(iterable $consumers, array $gateways, array $nonProxyCombinedGateways, ChannelResolver $channelResolver, array $consoleCommands)
    {
        Assert::allInstanceOfType($consumers, ConsumerLifecycle::class);
        Assert::allInstanceOfType($gateways, GatewayReference::class);

        $this->consumers = $consumers;
        $this->channelResolver = $channelResolver;
        $this->gatewayReferences = $gateways;
        $this->nonProxyCombinedGateways = $nonProxyCombinedGateways;

        $this->initialize();
        $this->consoleCommands = $consoleCommands;
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
     * @param bool $isLazyConfiguration
     * @param ConsoleCommandConfiguration[] $consoleCommands
     * @throws MessagingException
     */
    public static function createFrom(
        ReferenceSearchService $referenceSearchService,
        array $messageChannelBuilders, array $messageChannelInterceptors,
        array $gatewayBuilders, array $consumerFactories,
        array $pollingMetadataConfigurations, array $messageHandlerBuilders, array $channelAdapterConsumerBuilders,
        bool $isLazyConfiguration,
        array $consoleCommands
    ): \Ecotone\Messaging\Config\MessagingSystem
    {
        $channelResolver = self::createChannelResolver($messageChannelInterceptors, $messageChannelBuilders, $referenceSearchService);

        list($gateways, $nonProxyGateways) = self::configureGateways($gatewayBuilders, $referenceSearchService, $channelResolver, $isLazyConfiguration);

        $gatewayReferences = [];
        foreach ($gateways as $gateway) {
            $gatewayReferences[$gateway->getReferenceName()] = $gateway->getGateway();
            $referenceSearchService->registerReferencedObject($gateway->getReferenceName(), $gatewayReferences[$gateway->getReferenceName()]);
        }
        $referenceSearchService->registerReferencedObject(ChannelResolver::class, $channelResolver);
        $consumerEndpointFactory = new ConsumerEndpointFactory($channelResolver, $referenceSearchService, $consumerFactories, $pollingMetadataConfigurations);
        $consumers = [];

        foreach ($messageHandlerBuilders as $messageHandlerBuilder) {
            $consumers[] = $consumerEndpointFactory->createForMessageHandler($messageHandlerBuilder, $messageChannelBuilders);
        }

        foreach ($channelAdapterConsumerBuilders as $channelAdapter) {
            $consumers[] = $channelAdapter->build($channelResolver, $referenceSearchService, array_key_exists($channelAdapter->getEndpointId(), $pollingMetadataConfigurations) ? $pollingMetadataConfigurations[$channelAdapter->getEndpointId()] : PollingMetadata::create($channelAdapter->getEndpointId()));
        }

        return MessagingSystem::create($consumers, $gateways, $nonProxyGateways, $channelResolver, $consoleCommands);
    }

    /**
     * @param ChannelInterceptorBuilder[] $channelInterceptorBuilders
     * @param MessageChannelBuilder[] $channelBuilders
     * @param ReferenceSearchService $referenceSearchService
     * @throws MessagingException
     */
    private static function createChannelResolver(array $channelInterceptorBuilders, array $channelBuilders, ReferenceSearchService $referenceSearchService): \Ecotone\Messaging\Config\InMemoryChannelResolver
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
            $referenceName             = $preparedGatewaysForReference[0]->getReferenceName();

            if (count($preparedGatewaysForReference) === 1) {
                $gatewayProxyBuilder        = $preparedGatewaysForReference[0]
                    ->withLazyBuild($isLazyConfiguration);
                $nonProxyCombinedGateways[$referenceName] = NonProxyCombinedGateway::createWith($referenceName, [$gatewayProxyBuilder->getRelatedMethodName() => $gatewayProxyBuilder->buildWithoutProxyObject($referenceSearchService, $channelResolver)]);
                $gateways[$referenceName]                 = GatewayReference::createWith(
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

    /**
     * @param iterable $consumers
     * @param GatewayReference[]|array $gateways
     * @param NonProxyCombinedGateway[]|array $nonProxyCombinedGateways
     * @param ChannelResolver $channelResolver
     * @return MessagingSystem
     * @throws MessagingException
     * @internal
     */
    public static function create(iterable $consumers, array $gateways, array $nonProxyCombinedGateways, ChannelResolver $channelResolver, array $consoleCommands): self
    {
        return new self($consumers, $gateways, $nonProxyCombinedGateways, $channelResolver, $consoleCommands);
    }

    /**
     * @param string $endpointId
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function runAsynchronouslyRunningEndpoint(string $endpointId): void
    {
        foreach ($this->consumers as $consumer) {
            if ($consumer->getConsumerName() === $endpointId) {
                Assert::isTrue($consumer->isRunningInSeparateThread(), "Can't run event driven consumer with name {$endpointId} in separate thread");

                $consumer->run();
                return;
            }
        }

        throw InvalidArgumentException::create("There is nothing registered with name `{$endpointId}`. You can reverify the name using `ecotone:list` console command.");
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

    public function getNonProxyGatewayByName(string $gatewayReferenceName): \Ecotone\Messaging\Config\NonProxyCombinedGateway
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
            $arguments[$this->getParameterHeaderName($argumentName)] = $value;
        }
        foreach ($consoleCommandConfiguration->getParameters() as $commandParameter) {
            if (!array_key_exists($this->getParameterHeaderName($commandParameter->getName()), $arguments)) {
                if (!$commandParameter->hasDefaultValue()) {
                    throw InvalidArgumentException::create("Missing argument with name {$commandParameter->getName()} for console command {$commandName}");
                }

                $arguments[$this->getParameterHeaderName($commandParameter->getName())] = $commandParameter->getDefaultValue();
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
    public function getListOfAsynchronouslyRunningConsumers(): array
    {
        $list = [];

        foreach ($this->consumers as $consumer) {
            if ($consumer->isRunningInSeparateThread()) {
                $list[] = $consumer->getConsumerName();
            }
        }

        return $list;
    }

    /**
     * @param int|string $argumentName
     * @return string
     */
    private function getParameterHeaderName(int|string $argumentName): string
    {
        return ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . $argumentName;
    }
}