<?php

namespace Ecotone\Test;

use Ecotone\Lite\InMemoryContainerImplementation;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Compiler\RegisterInterfaceToCallReferences;
use Ecotone\Messaging\Config\Container\Compiler\RegisterSingletonMessagingServices;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\EndpointRunnerReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\ProxyBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\EndpointRunner;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;

use Ecotone\Messaging\InMemoryConfigurationVariableService;

use function get_class;

use Ramsey\Uuid\Uuid;

class ComponentTestBuilder
{
    private MessagingContainerBuilder $messagingBuilder;

    private function __construct(private InMemoryPSRContainer $container, private ContainerBuilder $builder)
    {
        $this->messagingBuilder = new MessagingContainerBuilder($builder);
    }

    public static function create(): self
    {
        $container = InMemoryPSRContainer::createFromAssociativeArray([
            ServiceCacheConfiguration::class => ServiceCacheConfiguration::noCache(),
            ConfigurationVariableService::REFERENCE_NAME => InMemoryConfigurationVariableService::createEmpty(),
        ]);
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addCompilerPass(new RegisterSingletonMessagingServices());
        $containerBuilder->addCompilerPass(new RegisterInterfaceToCallReferences());
        $containerBuilder->addCompilerPass(new InMemoryContainerImplementation($container));
        return new self($container, $containerBuilder);
    }

    public function withChannel(string $channelName, DefinedObject $channel): self
    {
        $this->messagingBuilder->register(new ChannelReference($channelName), $channel);

        return $this;
    }

    public function withPollingMetadata(PollingMetadata $pollingMetadata): self
    {
        $this->messagingBuilder->registerPollingMetadata($pollingMetadata);

        return $this;
    }

    public function withReference(string $referenceName, object $object): self
    {
        $this->messagingBuilder->register($referenceName, new Definition(get_class($object)));
        $this->container->set($referenceName, $object);

        return $this;
    }

    public function build(CompilableBuilder $compilableBuilder): mixed
    {
        $reference = $compilableBuilder->compile($this->messagingBuilder);
        if ($reference instanceof Definition) {
            $id = Uuid::uuid4();
            $this->builder->register($id, $reference);
            $referenceToReturn = new Reference($id);
        } else {
            $referenceToReturn = $reference;
        }

        $this->compile();
        return $this->container->get($referenceToReturn->getId());
    }

    public function buildWithProxy(ProxyBuilder $compilableBuilder): mixed
    {
        $referenceToReturn = $compilableBuilder->registerProxy($this->messagingBuilder);

        $this->compile();
        return $this->container->get($referenceToReturn->getId());
    }

    public function withRegisteredMessageHandlerConsumer(MessageHandlerConsumerBuilder $messageHandlerConsumerBuilder, MessageHandlerBuilder $messageHandlerBuilder): self
    {
        $messageHandlerConsumerBuilder->registerConsumer($this->messagingBuilder, $messageHandlerBuilder);

        return $this;
    }

    public function withRegisteredChannelAdapter(ChannelAdapterConsumerBuilder $channelAdapterConsumerBuilder): self
    {
        $channelAdapterConsumerBuilder->registerConsumer($this->messagingBuilder);

        return $this;
    }

    public function getEndpointRunner(string $endpointId): EndpointRunner
    {
        $this->compile();
        return $this->container->get(new EndpointRunnerReference($endpointId));
    }

    public function runEndpoint(string $endpointId, ?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        $this->getEndpointRunner($endpointId)->runEndpointWithExecutionPollingMetadata($executionPollingMetadata);
    }

    private function compile(): void
    {
        if (! $this->builder->has(ConversionService::REFERENCE_NAME)) {
            $this->builder->register(ConversionService::REFERENCE_NAME, new Definition(AutoCollectionConversionService::class, ['converters' => []], 'createWith'));
        }
        $this->builder->compile();
    }

    public function getGatewayByName(string $name)
    {
        return $this->container->get($name);
    }

    public function getBuilder(): MessagingContainerBuilder
    {
        return $this->messagingBuilder;
    }
}
