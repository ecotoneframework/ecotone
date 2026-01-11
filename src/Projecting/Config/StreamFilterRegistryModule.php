<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\EventSourcing\Attribute\AggregateType;
use Ecotone\EventSourcing\Attribute\FromAggregateStream;
use Ecotone\EventSourcing\Attribute\FromStream;
use Ecotone\EventSourcing\Attribute\Stream;
use Ecotone\EventSourcing\EventStore;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Ecotone\Projecting\Attribute\ProjectionV2;
use Ecotone\Projecting\Attribute\Streaming;
use Ecotone\Projecting\EventStoreAdapter\EventStreamingChannelAdapter;
use Ecotone\Projecting\StreamFilter;
use Ecotone\Projecting\StreamFilterRegistry;

#[ModuleAnnotation]
class StreamFilterRegistryModule implements AnnotationModule
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

        foreach ($annotationFinder->findAnnotatedClasses(ProjectionV2::class) as $classname) {
            $projectionAttribute = $annotationFinder->getAttributeForClass($classname, ProjectionV2::class);
            $projectionName = $projectionAttribute->name;
            $eventNames = $projectionEventNames[$projectionName] ?? [];

            foreach ($annotationFinder->getAnnotationsForClass($classname, FromStream::class) as $streamAttribute) {
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

            $isStreamingProjection = $annotationFinder->findAttributeForClass($classname, Streaming::class) !== null;
            if (! $isStreamingProjection && (! isset($streamFilters[$projectionName]) || $streamFilters[$projectionName] === [])) {
                throw ConfigurationException::create(
                    "Projection '{$projectionName}' must have at least one #[FromStream] or #[FromAggregateStream] attribute defined on class {$classname}."
                );
            }
        }

        return $streamFilters;
    }

    /**
     * @param array<class-string, string> $namedEvents Map of class name to named event name
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

        foreach ($annotationFinder->findCombined(ProjectionV2::class, EventHandler::class) as $projectionEventHandler) {
            /** @var ProjectionV2 $projectionAttribute */
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

    private static function resolveFromAggregateStream(
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

        $streamName = $aggregateClass;
        if (class_exists(Stream::class)) {
            $streamAttribute = $annotationFinder->findAttributeForClass($aggregateClass, Stream::class);
            $streamName = $streamAttribute?->getName() ?? $aggregateClass;
        }

        $aggregateType = $aggregateClass;
        $aggregateTypeAttribute = $annotationFinder->findAttributeForClass($aggregateClass, AggregateType::class);
        $aggregateType = $aggregateTypeAttribute?->getName() ?? $aggregateClass;

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
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof EventStreamingChannelAdapter;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions, ?InterfaceToCallRegistry $interfaceToCallRegistry = null): array
    {
        return [];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
