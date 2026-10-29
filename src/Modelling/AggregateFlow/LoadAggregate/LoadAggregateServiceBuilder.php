<?php

namespace Ecotone\Modelling\AggregateFlow\LoadAggregate;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Processor\InterceptedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Attribute\TargetAggregateVersion;
use Ecotone\Modelling\Repository\AllAggregateRepository;

/**
 * licence Apache-2.0
 */
class LoadAggregateServiceBuilder implements InterceptedMessageProcessorBuilder
{
    private string $aggregateClassName;
    private string $methodName;
    private ?string $messageVersionPropertyName;
    private LoadAggregateMode $loadAggregateMode;

    private function __construct(ClassDefinition $aggregateClassName, string $methodName, ?ClassDefinition $handledMessageClass, LoadAggregateMode $loadAggregateMode)
    {
        $this->aggregateClassName      = $aggregateClassName;
        $this->methodName              = $methodName;
        $this->loadAggregateMode = $loadAggregateMode;

        $this->initialize($handledMessageClass);
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, ?ClassDefinition $handledMessageClass, LoadAggregateMode $loadAggregateMode): self
    {
        return new self($aggregateClassDefinition, $methodName, $handledMessageClass, $loadAggregateMode);
    }

    public function compile(MessagingContainerBuilder $builder, array $aroundInterceptors = []): Definition
    {
        if (! $builder->has(PropertyEditorAccessor::class)) {
            $builder->register(PropertyEditorAccessor::class, new Definition(PropertyEditorAccessor::class, [
                new Reference(ExpressionEvaluationService::REFERENCE),
            ], 'create'));
        }

        return new Definition(LoadAggregateMessageProcessor::class, [
            new Reference(AllAggregateRepository::class),
            $this->aggregateClassName,
            $this->methodName,
            $this->messageVersionPropertyName,
            new Reference(PropertyReaderAccessor::class),
            new Definition(LoadAggregateMode::class, [$this->loadAggregateMode->getType()]),
        ]);
    }

    private function initialize(?ClassDefinition $handledMessageClassName): void
    {
        $aggregateMessageVersionPropertyName = null;
        if ($handledMessageClassName) {
            $targetAggregateVersion            = Type::attribute(TargetAggregateVersion::class);
            foreach ($handledMessageClassName->getProperties() as $property) {
                if ($property->hasAnnotation($targetAggregateVersion)) {
                    $aggregateMessageVersionPropertyName = $property->getName();
                }
            }
        }

        $this->messageVersionPropertyName = $aggregateMessageVersionPropertyName;
    }

    public function getInterceptedInterface(): InterfaceToCallReference
    {
        return InterfaceToCallReference::create($this->aggregateClassName, $this->methodName);
    }
}
