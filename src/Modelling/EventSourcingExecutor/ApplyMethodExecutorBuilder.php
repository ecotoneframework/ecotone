<?php

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Api\Attribute\Apply;
use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\ApplyMethod;
use ReflectionClass;

/**
 * licence Apache-2.0
 */
final class ApplyMethodExecutorBuilder
{
    public static function createFor(ClassDefinition $classDefinition, InterfaceToCallRegistry $interfaceToCallRegistry): Definition
    {
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        $class = new ReflectionClass($classDefinition->getClassType()->toString());

        if ($class->hasMethod('__construct')) {
            $constructMethod = $class->getMethod('__construct');

            if ($constructMethod->getParameters()) {
                throw ConfigurationException::create("Constructor for Event Sourced {$classDefinition} should not have any parameters");
            }
            if (! $constructMethod->isPublic()) {
                throw ConfigurationException::create("Constructor for Event Sourced {$classDefinition} should be public");
            }
        }

        $aggregateFactoryAnnotation = Type::object(Apply::class);
        $applyMethods = [];
        foreach ($classDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($classDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                if ($methodToCheck->isStaticallyCalled()) {
                    throw ConfigurationException::create("{$methodToCheck} is an #[Apply] method and should not be static.");
                }
                if ($methodToCheck->getInterfaceParameterAmount() < 1) {
                    throw ConfigurationException::create("{$methodToCheck} is an #[Apply] method and should have at least one parameter.");
                }
                if (! $methodToCheck->getFirstParameter()->isClassOrInterface()) {
                    throw ConfigurationException::create("{$methodToCheck} is an #[Apply] method and should have first parameter as Event Class or Interface type hint.");
                }
                if (! $methodToCheck->hasReturnTypeVoid()) {
                    throw ConfigurationException::create("{$methodToCheck} is an #[Apply] method and should return void return type");
                }

                $applyMethods[$method] = ApplyMethod::prepareDefinition(
                    $methodToCheck,
                    $parameterConverterFactory->createParameterWithDefaults($methodToCheck)
                );
            }
        }

        if (! $applyMethods) {
            throw ConfigurationException::create("Your aggregate {$classDefinition->getClassType()}, is event sourced. You must define at least one #[Apply] method to provide aggregate's identifier after first event.");
        }

        return new Definition(ApplyMethodExecutor::class, [
            $classDefinition->getClassType()->toString(),
            $applyMethods,
            LicenceDecider::prepareDefinition(AggregateMethodInvoker::class, OpenCoreAggregateMethodInvoker::class, EnterpriseAggregateMethodInvoker::class),
            Reference::to(EventMapper::class),
        ]);
    }
}
