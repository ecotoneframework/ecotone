<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver;

use Ecotone\EventSourcing\Attribute\AggregateType;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Attribute\AggregateEvents;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;

/**
 * licence Apache-2.0
 */
final class AggregateDefinitionResolver
{
    public static function resolve(string $aggregateClass, InterfaceToCallRegistry $interfaceToCallRegistry): AggregateClassDefinition
    {
        $aggregateClassDefinition = $interfaceToCallRegistry->getClassDefinitionFor(Type::object($aggregateClass));

        $isAggregateEventSourced = $aggregateClassDefinition->hasClassAnnotation(Type::attribute(EventSourcingAggregate::class));

        [$calledAggregateIdentifierMapping, $calledAggregateIdentifierGetMethods] = self::resolveAggregateIdentifierMapping($aggregateClassDefinition, $interfaceToCallRegistry);
        [$calledAggregateVersionProperty, $isCalledAggregateVersionAutomaticallyIncreased]  = self::resolveAggregateVersionProperty($aggregateClassDefinition);


        $aggregateIdentifierAnnotation = Type::attribute(Identifier::class);
        $aggregateIdentifiers = [];
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $aggregateIdentifiers[$property->getName()] = null;
            }
        }

        $eventRecorderMethodAnnotation = Type::attribute(AggregateEvents::class);
        $eventRecorderMethod = null;
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $method);
            if ($methodToCheck->hasMethodAnnotation($eventRecorderMethodAnnotation)) {
                if ($methodToCheck->getReturnType()->isVoid()) {
                    throw NoCorrectIdentifierDefinedException::create($methodToCheck . ' should return events, and can\'t be void.');
                }

                $eventRecorderMethod = $method;
            }
        }

        return new AggregateClassDefinition(
            $aggregateClass,
            $isAggregateEventSourced,
            $eventRecorderMethod,
            $calledAggregateVersionProperty,
            $isCalledAggregateVersionAutomaticallyIncreased,
            $calledAggregateIdentifierMapping,
            $calledAggregateIdentifierGetMethods,
            self::getAggregateType($aggregateClassDefinition),
        );
    }

    private static function getAggregateType(ClassDefinition $classDefinition): string
    {
        foreach ($classDefinition->getClassAnnotations() as $annotation) {
            if ($annotation instanceof AggregateType) {
                return $annotation->getName();
            }
        }

        return $classDefinition->getClassType()->toString();
    }

    private static function resolveAggregateIdentifierMapping(ClassDefinition $aggregateClassDefinition, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $aggregateIdentifierGetMethodAttribute = Type::attribute(AggregateIdentifierMethod::class);
        $aggregateIdentifiers = [];
        $aggregateIdentifierGetMethods = [];

        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $method);
            if ($methodToCheck->hasMethodAnnotation($aggregateIdentifierGetMethodAttribute)) {
                if (! $methodToCheck->hasNoParameters()) {
                    throw NoCorrectIdentifierDefinedException::create($methodToCheck . ' should not have any parameters.');
                }

                /** @var AggregateIdentifierMethod $attribute */
                $attribute = $methodToCheck->getSingleMethodAnnotationOf($aggregateIdentifierGetMethodAttribute);
                $aggregateIdentifiers[$attribute->getIdentifierPropertyName()] = null;
                $aggregateIdentifierGetMethods[$attribute->getIdentifierPropertyName()] = $method;
            }
        }

        $aggregateIdentifierAnnotation = Type::attribute(AggregateIdentifier::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $aggregateIdentifiers[$property->getName()] = null;
            }
        }

        return [$aggregateIdentifiers, $aggregateIdentifierGetMethods];
    }

    private static function resolveAggregateVersionProperty(ClassDefinition $aggregateClassDefinition): array
    {
        $aggregateVersionPropertyName = null;
        $isAggregateVersionAutomaticallyIncreased = false;
        $versionAnnotation = Type::attribute(AggregateVersion::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
                /** @var AggregateVersion $annotation */
                $annotation = $property->getAnnotation($versionAnnotation);
                $isAggregateVersionAutomaticallyIncreased = $annotation->isAutoIncreased();
            }
        }

        return [$aggregateVersionPropertyName, $isAggregateVersionAutomaticallyIncreased];
    }
}
