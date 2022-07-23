<?php

namespace Ecotone\EventSourcing\Config;

use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\EventSourcing\ProjectionSetupConfiguration;
use Ecotone\EventSourcing\Prooph\LazyProophProjectionManager;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHandler;

class ProjectionManagerBuilder extends InputOutputMessageHandlerBuilder
{
    /**
     * @param ParameterConverterBuilder[] $parameterConverters
     * @param ProjectionSetupConfiguration[] $projectionSetupConfigurations
     */
    private function __construct(
        private string $methodName,
        private array $parameterConverters,
        private EventSourcingConfiguration $eventSourcingConfiguration,
        private array $projectionSetupConfigurations
    ) {
    }

    /**
     * @param ParameterConverterBuilder[] $parameterConverters
     * @param ProjectionSetupConfiguration[] $projectionSetupConfigurations
     */
    public static function create(
        string $methodName,
        array $parameterConverters,
        EventSourcingConfiguration $eventSourcingConfiguration,
        array $projectionSetupConfigurations
    ): static {
        return new self($methodName, $parameterConverters, $eventSourcingConfiguration, $projectionSetupConfigurations);
    }

    public function getInputMessageChannelName(): string
    {
        return $this->getProjectionManagerActionChannel($this->eventSourcingConfiguration->getProjectManagerReferenceName(), $this->methodName);
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(LazyProophProjectionManager::class, $this->methodName);
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        return ServiceActivatorBuilder::createWithDirectReference(
            new LazyProophProjectionManager($this->eventSourcingConfiguration, $this->projectionSetupConfigurations, $referenceSearchService),
            $this->methodName
        )
            ->withMethodParameterConverters($this->parameterConverters)
            ->withInputChannelName($this->getInputMessageChannelName())
            ->build($channelResolver, $referenceSearchService);
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [$interfaceToCallRegistry->getFor(LazyProophProjectionManager::class, $this->methodName)];
    }

    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    public static function getProjectionManagerActionChannel(string $projectionManagerReference, string $methodName): string
    {
        return $projectionManagerReference . $methodName;
    }
}
