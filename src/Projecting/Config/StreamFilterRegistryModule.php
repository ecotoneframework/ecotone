<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\NamedEvent;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Projecting\EventStreamingChannelAdapter;
use Ecotone\Api\Projecting\FromAggregateStream;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\FromTaggedStream;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\Streaming;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionResolver;
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Ecotone\Projecting\StreamFilter;
use Ecotone\Projecting\StreamFilterRegistry;
use Ecotone\Projecting\StreamSourceReference;
use Ecotone\Projecting\TaggedStream\TagStreamSource;

#[ModuleAnnotation]
final class StreamFilterRegistryModule implements AnnotationModule
{
    /** @param array<string, StreamFilter[]> $streamFilters */
    public function __construct(private array $streamFilters)
    {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self(self::collectStreamFilters($annotationRegistrationService, $interfaceToCallRegistry));
    }

    /**
     * @return array<string, StreamFilter[]> Map of projection name to stream filters
     */
    public static function collectStreamFilters(AnnotationFinder $annotationFinder, ?InterfaceToCallRegistry $interfaceToCallRegistry = null): array
    {
        $projectionEventNames = $interfaceToCallRegistry !== null
            ? self::collectProjectionEventNames($annotationFinder, $interfaceToCallRegistry)
            : [];

        $streamFilters = [];

        foreach ($annotationFinder->findAnnotatedClasses(Projection::class) as $classname) {
            $projectionAttribute = $annotationFinder->getAttributeForClass($classname, Projection::class);
            $projectionName = $projectionAttribute->name;
            $eventNames = $projectionEventNames[$projectionName] ?? [];

            foreach ($annotationFinder->getAnnotationsForClass($classname, FromStream::class) as $streamAttribute) {
                self::assertIsNotAggregateClass($annotationFinder, $streamAttribute->stream, $projectionName, $classname);
                $streamFilters[$projectionName][] = new StreamFilter(
                    $streamAttribute->stream,
                    $streamAttribute->aggregateType,
                    $streamAttribute->eventStoreReferenceName,
                    $eventNames,
                );
            }

            foreach ($annotationFinder->getAnnotationsForClass($classname, FromAggregateStream::class) as $aggregateStreamAttribute) {
                $streamFilters[$projectionName][] = self::resolveFromAggregateStream($annotationFinder, $aggregateStreamAttribute, $projectionName, $eventNames);
            }

            foreach ($annotationFinder->getAnnotationsForClass($classname, FromTaggedStream::class) as $taggedStreamAttribute) {
                $streamFilters[$projectionName][] = new StreamFilter('', null, $taggedStreamAttribute->eventStoreReferenceName, $eventNames, $taggedStreamAttribute->tag);
            }

            $isStreamingProjection = $annotationFinder->findAttributeForClass($classname, Streaming::class) !== null;
            if (! $isStreamingProjection && (! isset($streamFilters[$projectionName]) || $streamFilters[$projectionName] === [])) {
                throw ConfigurationException::create(
                    "Projection '{$projectionName}' must have at least one #[FromStream], #[FromAggregateStream] or #[FromTaggedStream] attribute defined on class {$classname}."
                );
            }
        }

        return $streamFilters;
    }

    /**
     * @return array<string, array<string>> Map of projection name to event names (empty array means no filtering)
     */
    public static function collectProjectionEventNames(
        AnnotationFinder $annotationFinder,
        InterfaceToCallRegistry $interfaceToCallRegistry,
    ): array {
        $namedEvents = [];
        foreach ($annotationFinder->findAnnotatedClasses(NamedEvent::class) as $className) {
            $attribute = $annotationFinder->getAttributeForClass($className, NamedEvent::class);
            $namedEvents[$className] = $attribute->getName();
        }

        $projectionEventNames = [];
        $disabledFiltering = [];
        $routingMapBuilder = new BusRoutingMapBuilder();

        foreach ($annotationFinder->findCombined(Projection::class, EventHandler::class) as $projectionEventHandler) {
            /** @var Projection $projectionAttribute */
            $projectionAttribute = $projectionEventHandler->getAnnotationForClass();
            $projectionName = $projectionAttribute->name;

            if (! isset($projectionEventNames[$projectionName])) {
                $projectionEventNames[$projectionName] = [];
            }

            if (isset($disabledFiltering[$projectionName])) {
                continue;
            }

            $routes = $routingMapBuilder->getRoutesFromAnnotatedFinding($projectionEventHandler, $interfaceToCallRegistry);
            foreach ($routes as $route) {
                if ($route === '*' || $route === 'object') {
                    $projectionEventNames[$projectionName] = [];
                    $disabledFiltering[$projectionName] = true;
                    break;
                }

                if (str_contains($route, '*')) {
                    throw ConfigurationException::create(
                        "Projection {$projectionName} uses glob pattern '{$route}' which is not allowed. " .
                        'For query optimization, event handlers must use explicit event names. Use union type parameters instead.'
                    );
                }

                if (class_exists($route) && isset($namedEvents[$route])) {
                    $projectionEventNames[$projectionName][] = $namedEvents[$route];
                } else {
                    $projectionEventNames[$projectionName][] = $route;
                }
            }
        }

        foreach ($projectionEventNames as $projectionName => $eventNames) {
            if (! isset($disabledFiltering[$projectionName]) && $eventNames !== []) {
                $projectionEventNames[$projectionName] = array_values(array_unique($eventNames));
            }
        }

        return $projectionEventNames;
    }

    private static function assertIsNotAggregateClass(AnnotationFinder $annotationFinder, string $streamName, string $projectionName, string $projectionClassName): void
    {
        if (! class_exists($streamName)) {
            return;
        }

        if ($annotationFinder->findAttributeForClass($streamName, EventSourcingAggregate::class) === null) {
            return;
        }

        throw ConfigurationException::create(
            "Projection '{$projectionName}' on {$projectionClassName} uses #[FromStream({$streamName}::class)] pointing to an Event Sourcing Aggregate. "
            . "Use #[FromAggregateStream({$streamName}::class)] instead, so events are filtered by aggregate type."
        );
    }

    public static function resolveFromAggregateStream(
        AnnotationFinder $annotationFinder,
        FromAggregateStream $attribute,
        string $projectionName,
        array $eventNames = []
    ): StreamFilter {
        $aggregateClass = $attribute->aggregateClass;

        $eventSourcingAggregateAttribute = $annotationFinder->findAttributeForClass($aggregateClass, EventSourcingAggregate::class);
        if ($eventSourcingAggregateAttribute === null) {
            throw ConfigurationException::create("Class {$aggregateClass} referenced in #[FromAggregateStream] for projection {$projectionName} must be an EventSourcingAggregate.");
        }

        $streamName = AggregateDefinitionResolver::resolveStreamNameFromFinder($annotationFinder, $aggregateClass);
        $aggregateType = AggregateDefinitionResolver::resolveAggregateTypeFromFinder($annotationFinder, $aggregateClass);

        return new StreamFilter($streamName, $aggregateType, $attribute->eventStoreReferenceName, $eventNames);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $filtersDefinition = [];
        foreach ($this->streamFilters as $projectionName => $filters) {
            $filtersDefinition[$projectionName] = [];
            foreach ($filters as $filter) {
                $filtersDefinition[$projectionName][] = new Definition(StreamFilter::class, [
                    $filter->streamName,
                    $filter->aggregateType,
                    $filter->eventStoreReferenceName,
                    $filter->eventNames,
                    $filter->tag,
                ]);
            }
        }

        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof EventStreamingChannelAdapter) {
                $projectionName = $extensionObject->getProjectionName();
                $filtersDefinition[$projectionName] = [
                    new Definition(StreamFilter::class, [
                        $extensionObject->fromStream,
                        $extensionObject->aggregateType,
                        EventStore::class,
                        $extensionObject->eventNames,
                    ]),
                ];
            }
        }

        $messagingConfiguration->registerServiceDefinition(
            StreamFilterRegistry::class,
            new Definition(StreamFilterRegistry::class, [$filtersDefinition])
        );

        $tagPartitionedProjectionNames = self::tagPartitionedProjectionNamesOf($this->streamFilters);
        if ($tagPartitionedProjectionNames !== []) {
            $messagingConfiguration->registerServiceDefinition(
                TagStreamSource::class,
                new Definition(TagStreamSource::class, [
                    new Reference(EventStore::class),
                    new Reference(StreamFilterRegistry::class),
                    $tagPartitionedProjectionNames,
                ])
            );
        }
    }

    /**
     * @return string[]
     */
    public static function tagPartitionedProjectionNames(AnnotationFinder $annotationFinder): array
    {
        return self::tagPartitionedProjectionNamesOf(self::collectStreamFilters($annotationFinder));
    }

    /**
     * @param array<string, StreamFilter[]> $streamFilters
     * @return string[]
     */
    private static function tagPartitionedProjectionNamesOf(array $streamFilters): array
    {
        $names = [];
        foreach ($streamFilters as $projectionName => $filters) {
            foreach ($filters as $filter) {
                if ($filter->tag !== null) {
                    $names[] = $projectionName;
                    break;
                }
            }
        }

        return $names;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions, ?InterfaceToCallRegistry $interfaceToCallRegistry = null): array
    {
        return self::tagPartitionedProjectionNamesOf($this->streamFilters) === [] ? [] : [new StreamSourceReference(TagStreamSource::class)];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
