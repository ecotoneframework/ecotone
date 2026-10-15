<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\EventSourcing\EventSourcingConfiguration;
use Ecotone\Api\EventSourcing\Stream;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Type;
use ReflectionAttribute;
use ReflectionClass;

use function sprintf;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class CrossConnectionDecisionModelGuard
{
    /**
     * @param array<class-string, array{tagNames: string[], literalTagValues: array<string, string>, handledEventClasses: class-string[], aggregate?: array{className: class-string, aggregateType: string, streamName: string, identifierNames: string[]}}> $rawDefinitions
     * @param array<object> $extensionObjects
     */
    public static function assertNoCrossConnectionInjection(
        AnnotationFinder $annotationFinder,
        InterfaceToCallRegistry $interfaceToCallRegistry,
        array $extensionObjects,
        array $rawDefinitions,
    ): void {
        if (! class_exists(EventSourcingConfiguration::class) || ! class_exists(Stream::class)) {
            return;
        }

        $defaultConnection = ExtensionObjectResolver::resolveUnique(
            EventSourcingConfiguration::class,
            $extensionObjects,
            EventSourcingConfiguration::createWithDefaults()
        )->getConnectionReferenceName();

        [$aggregateConnections, $eventRecordingAggregates] = self::buildAggregateConnectionsAndEvents($annotationFinder, $interfaceToCallRegistry, $defaultConnection);

        foreach (self::findModelInjectingHandlers($annotationFinder, $interfaceToCallRegistry, $rawDefinitions) as $handler) {
            $handlerConnection = self::connectionForHandler($annotationFinder, $interfaceToCallRegistry, $handler['class'], $handler['method'], $aggregateConnections, $defaultConnection);

            foreach ($handler['models'] as $modelClass) {
                $backingAggregate = $rawDefinitions[$modelClass]['aggregate'] ?? null;

                if ($backingAggregate !== null) {
                    $aggregateConnection = self::classConnection($backingAggregate['className'], $defaultConnection);

                    if ($aggregateConnection !== $handlerConnection) {
                        throw ConfigurationException::create(sprintf(
                            "DecisionModel %s is injected into %s::%s, which appends to connection '%s', but it is backed by aggregate %s, whose stream '%s' is on connection '%s' -- a model folded from one connection cannot be guarded by an append on another. "
                            . 'Move the handler and the aggregate onto the same connection.',
                            $modelClass,
                            $handler['class'],
                            $handler['method'],
                            $handlerConnection,
                            $backingAggregate['className'],
                            $backingAggregate['streamName'],
                            $aggregateConnection,
                        ));
                    }

                    continue;
                }

                foreach ($rawDefinitions[$modelClass]['handledEventClasses'] as $eventClass) {
                    foreach ($eventRecordingAggregates[$eventClass] ?? [] as $aggregateClass) {
                        $eventConnection = $aggregateConnections[$aggregateClass];

                        if ($eventConnection !== $handlerConnection) {
                            throw ConfigurationException::create(sprintf(
                                "DecisionModel %s is injected into %s::%s, which appends to connection '%s', but its handled event %s "
                                . "is recorded by %s on connection '%s' -- a model loaded from one connection cannot see events committed "
                                . 'on another. Move the handler and its models onto the same connection.',
                                $modelClass,
                                $handler['class'],
                                $handler['method'],
                                $handlerConnection,
                                $eventClass,
                                $aggregateClass,
                                $eventConnection,
                            ));
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array{0: array<class-string, string>, 1: array<class-string, class-string[]>}
     */
    private static function buildAggregateConnectionsAndEvents(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry, string $defaultConnection): array
    {
        $aggregateConnections = [];
        $eventRecordingAggregates = [];

        foreach ($annotationFinder->findAnnotatedMethods(Apply::class) as $annotatedMethod) {
            $className = $annotatedMethod->getClassName();

            if (! isset($aggregateConnections[$className])) {
                if ((new ReflectionClass($className))->getAttributes(Aggregate::class, ReflectionAttribute::IS_INSTANCEOF) === []) {
                    continue;
                }

                $aggregateConnections[$className] = self::classConnection($className, $defaultConnection);
            }

            $interfaceToCall = $interfaceToCallRegistry->getFor($className, $annotatedMethod->getMethodName());
            if ($interfaceToCall->getInterfaceParameterAmount() < 1 || ! $interfaceToCall->getFirstParameter()->isClassOrInterface()) {
                continue;
            }

            $eventClass = $interfaceToCall->getFirstParameter()->getTypeHint();
            if (! in_array($className, $eventRecordingAggregates[$eventClass] ?? [], true)) {
                $eventRecordingAggregates[$eventClass][] = $className;
            }
        }

        return [$aggregateConnections, $eventRecordingAggregates];
    }

    private static function classConnection(string $className, string $defaultConnection): string
    {
        $attributes = (new ReflectionClass($className))->getAttributes(Stream::class);
        if ($attributes === []) {
            return $defaultConnection;
        }

        /** @var Stream $stream */
        $stream = $attributes[0]->newInstance();

        return $stream->getConnectionReferenceName();
    }

    /**
     * @param array<class-string, array{tagNames: string[], literalTagValues: array<string, string>, handledEventClasses: class-string[], aggregate?: array{className: class-string, aggregateType: string, streamName: string, identifierNames: string[]}}> $rawDefinitions
     * @return array<array{class: class-string, method: string, models: class-string[]}>
     */
    private static function findModelInjectingHandlers(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry, array $rawDefinitions): array
    {
        $modelClasses = array_keys($rawDefinitions);
        $handlers = [];

        foreach ([CommandHandler::class, EventHandler::class] as $handlerAnnotationClass) {
            foreach ($annotationFinder->findAnnotatedMethods($handlerAnnotationClass) as $annotatedMethod) {
                $className = $annotatedMethod->getClassName();
                $methodName = $annotatedMethod->getMethodName();
                $interfaceToCall = $interfaceToCallRegistry->getFor($className, $methodName);

                $injectedModels = [];
                foreach ($interfaceToCall->getInterfaceParameters() as $parameter) {
                    if (! $parameter->isClassOrInterface()) {
                        continue;
                    }

                    $typeHint = $parameter->getTypeHint();
                    if (in_array($typeHint, $modelClasses, true) && ! in_array($typeHint, $injectedModels, true)) {
                        $injectedModels[] = $typeHint;
                    }
                }

                if ($injectedModels !== []) {
                    $handlers[] = ['class' => $className, 'method' => $methodName, 'models' => $injectedModels];
                }
            }
        }

        return $handlers;
    }

    /**
     * @param array<class-string, string> $aggregateConnections
     */
    private static function connectionForHandler(
        AnnotationFinder $annotationFinder,
        InterfaceToCallRegistry $interfaceToCallRegistry,
        string $className,
        string $methodName,
        array $aggregateConnections,
        string $defaultConnection,
    ): string {
        if (isset($aggregateConnections[$className])) {
            return $aggregateConnections[$className];
        }

        $interfaceToCall = $interfaceToCallRegistry->getFor($className, $methodName);
        $methodStream = $interfaceToCall->findSingleMethodAnnotation(Type::create(Stream::class));
        if ($methodStream instanceof Stream) {
            return $methodStream->getConnectionReferenceName();
        }

        return self::classConnection($className, $defaultConnection);
    }
}
