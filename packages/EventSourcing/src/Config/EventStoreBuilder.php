<?php

namespace Ecotone\EventSourcing\Config;

use Ecotone\EventSourcing\EcotoneEventStoreProophWrapper;
use Ecotone\EventSourcing\EventMapper;
use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\EventSourcing\LazyProophEventStore;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHandler;

class EventStoreBuilder extends InputOutputMessageHandlerBuilder
{
    private string $methodName;
    private EventSourcingConfiguration $eventSourcingConfiguration;
    private array $parameterConverters;

    private function __construct(string $methodName, array $parameterConverters, EventSourcingConfiguration $eventSourcingConfiguration)
    {
        $this->methodName = $methodName;
        $this->parameterConverters = $parameterConverters;
        $this->eventSourcingConfiguration = $eventSourcingConfiguration;
        $this->inputMessageChannelName = $this->eventSourcingConfiguration->getEventStoreReferenceName() . $this->methodName;
    }

    /**
     * @param ParameterConverterBuilder[] $parameterConverters
     */
    public static function create(string $methodName, array $parameterConverters, EventSourcingConfiguration $eventSourcingConfiguration): static
    {
        return new self($methodName, $parameterConverters, $eventSourcingConfiguration);
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(EcotoneEventStoreProophWrapper::class, $this->methodName);
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        return ServiceActivatorBuilder::createWithDirectReference(
            EcotoneEventStoreProophWrapper::prepare(
                new LazyProophEventStore($this->eventSourcingConfiguration, $referenceSearchService),
                $referenceSearchService->get(ConversionService::REFERENCE_NAME),
                $referenceSearchService->get(EventMapper::class)
            ),
            $this->methodName
        )
            ->withMethodParameterConverters($this->parameterConverters)
            ->withInputChannelName($this->getInputMessageChannelName())
            ->build($channelResolver, $referenceSearchService);
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [$interfaceToCallRegistry->getFor(EcotoneEventStoreProophWrapper::class, $this->methodName)];
    }

    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}
