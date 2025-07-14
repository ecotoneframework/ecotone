<?php

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\ChannelResolverWithContainer;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\Container\ReferenceSearchServiceWithContainer;
use Ecotone\Messaging\Config\MessagingSystemContainer;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Handler\Bridge\Bridge;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
class RegisterSingletonMessagingServices implements CompilerPass
{
    public function process(ContainerBuilder $builder): void
    {
        $this->registerDefault($builder, Bridge::class, new Definition(Bridge::class));
        $this->registerDefault($builder, Reference::toChannel(NullableMessageChannel::CHANNEL_NAME), new Definition(NullableMessageChannel::class));
        $this->registerDefault($builder, EcotoneClockInterface::class, new Definition(Clock::class, [new Reference(ClockInterface::class, ContainerImplementation::NULL_ON_INVALID_REFERENCE)]));
        $this->registerDefault($builder, ChannelResolver::class, new Definition(ChannelResolverWithContainer::class, [new Reference(ContainerInterface::class)]));
        $this->registerDefault($builder, ReferenceSearchService::class, new Definition(ReferenceSearchServiceWithContainer::class, [new Reference(ContainerInterface::class)]));
        $this->registerDefault($builder, ExpressionEvaluationService::REFERENCE, new Definition(SymfonyExpressionEvaluationAdapter::class, [new Reference(ReferenceSearchService::class)], 'create'));
        $this->registerDefault($builder, ProxyFactory::class, new Definition(ProxyFactory::class, [new Reference(ServiceCacheConfiguration::REFERENCE_NAME)]));
        $this->registerDefault($builder, PropertyEditorAccessor::class, new Definition(PropertyEditorAccessor::class, [new Reference(ExpressionEvaluationService::REFERENCE)], 'create'));
        $this->registerDefault($builder, PropertyReaderAccessor::class, new Definition(PropertyReaderAccessor::class));
        $this->registerDefault($builder, ConfiguredMessagingSystem::class, new Definition(MessagingSystemContainer::class, [new Reference(ContainerInterface::class), [], []]));
        $this->registerDefault($builder, EventMapper::class, new Definition(EventMapper::class, factory: 'createEmpty'));
    }

    private function registerDefault(ContainerBuilder $builder, string $id, Definition|Reference $definition): void
    {
        if (! $builder->has($id)) {
            $builder->register($id, $definition);
        }
    }
}
