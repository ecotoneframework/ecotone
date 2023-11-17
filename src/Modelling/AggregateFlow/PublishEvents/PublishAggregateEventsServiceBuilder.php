<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\PublishEvents;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Modelling\EventBus;

final class PublishAggregateEventsServiceBuilder extends InputOutputMessageHandlerBuilder
{
    private InterfaceToCall $interfaceToCall;

    private function __construct(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry)
    {
        $this->initialize($aggregateClassDefinition, $methodName, $interfaceToCallRegistry);
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        return new self($aggregateClassDefinition, $methodName, $interfaceToCallRegistry);
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        $publishAggregateEventsService = new Definition(PublishAggregateEventsService::class, [
            $this->interfaceToCall->toString(),
            new Reference(EventBus::class),
        ]);

        return ServiceActivatorBuilder::createWithDefinition($publishAggregateEventsService, 'publish')
            ->withOutputMessageChannel($this->outputMessageChannelName)
            ->compile($builder);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(PublishAggregateEventsService::class, 'publish');
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $methodName);

        $this->interfaceToCall = $interfaceToCall;
    }
}
