<?php

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\EventSourcingHandlerMethod;
use ReflectionClass;

/**
 * licence Apache-2.0
 */
final class EventSourcingHandlerExecutorBuilder
{
    public static function createFor(ClassDefinition $classDefinition, InterfaceToCallRegistry $interfaceToCallRegistry): Definition
    {
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        $class = new ReflectionClass($classDefinition->getClassType()->toString());

        if ($class->hasMethod('__construct')) {
            $constructMethod = $class->getMethod('__construct');

            if ($constructMethod->getParameters()) {
                throw InvalidArgumentException::create("Constructor for Event Sourced {$classDefinition} should not have any parameters");
            }
            if (! $constructMethod->isPublic()) {
                throw InvalidArgumentException::create("Constructor for Event Sourced {$classDefinition} should be public");
            }
        }

        $aggregateFactoryAnnotation = TypeDescriptor::create(EventSourcingHandler::class);
        $eventSourcingHandlerMethods = [];
        foreach ($classDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($classDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                if ($methodToCheck->isStaticallyCalled()) {
                    throw InvalidArgumentException::create("{$methodToCheck} is Event Sourcing Handler and should not be static.");
                }
                if ($methodToCheck->getInterfaceParameterAmount() < 1) {
                    throw InvalidArgumentException::create("{$methodToCheck} is Event Sourcing Handler and should have at least one parameter.");
                }
                if (! $methodToCheck->getFirstParameter()->isObjectTypeHint()) {
                    throw InvalidArgumentException::create("{$methodToCheck} is Event Sourcing Handler and should have first parameter as Event Class type hint.");
                }
                if (! $methodToCheck->hasReturnTypeVoid()) {
                    throw InvalidArgumentException::create("{$methodToCheck} is Event Sourcing Handler and should return void return type");
                }

                $eventSourcingHandlerMethods[$method] = EventSourcingHandlerMethod::prepareDefinition(
                    $methodToCheck,
                    $parameterConverterFactory->createParameterWithDefaults($methodToCheck)
                );
            }
        }

        if (! $eventSourcingHandlerMethods) {
            throw InvalidArgumentException::create("Your aggregate {$classDefinition->getClassType()}, is event sourced. You must define at least one EventSourcingHandler to provide aggregate's identifier after first event.");
        }

        return new Definition(EventSourcingHandlerExecutor::class, [
            $classDefinition->getClassType()->toString(),
            $eventSourcingHandlerMethods,
            LicenceDecider::prepareDefinition(AggregateMethodInvoker::class, Reference::to(OpenCoreAggregateMethodInvoker::class), Reference::to(EnterpriseAggregateMethodInvoker::class)),
            Reference::to(EventMapper::class),
        ]);
    }
}
