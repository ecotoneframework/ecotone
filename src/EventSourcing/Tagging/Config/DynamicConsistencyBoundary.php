<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging\Config;

use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\EventSourcing\EventStore\AppendStrategy\AppendStrategy;
use Ecotone\EventSourcing\EventStore\AppendStrategy\DynamicConsistencyBoundaryStrategy;
use Ecotone\EventSourcing\EventStore\AppendStrategy\StandardConsistencyBoundaryStrategy;
use Ecotone\EventSourcing\EventStore\Tag\EnterpriseInMemoryTagCollaborator;
use Ecotone\EventSourcing\EventStore\Tag\InMemoryTagCollaborator;
use Ecotone\EventSourcing\EventStore\Tag\OpenCoreInMemoryTagCollaborator;
use Ecotone\EventSourcing\Tagging\TagResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;

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
final class DynamicConsistencyBoundary
{
    private function __construct(
        private readonly bool $enabled,
        private readonly DynamicConsistencyBoundaryConfiguration $configuration,
    ) {
    }

    /**
     * @param object[] $extensionObjects
     */
    public static function resolveFrom(array $extensionObjects): self
    {
        return new self(
            ExtensionObjectResolver::contains(DynamicConsistencyBoundaryConfiguration::class, $extensionObjects),
            ExtensionObjectResolver::resolveUnique(DynamicConsistencyBoundaryConfiguration::class, $extensionObjects, DynamicConsistencyBoundaryConfiguration::createWithDefaults()),
        );
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string[]
     */
    public function filterOnlyTagNames(): array
    {
        return $this->configuration->filterOnlyTagNames;
    }

    /**
     * @return array<class-string, array{thresholdTrigger: int, documentStore: string}>
     */
    public function snapshottedModelClasses(): array
    {
        return $this->configuration->snapshottedModelClasses;
    }

    /**
     * @return class-string[]
     */
    public function classesWithoutCounterTag(): array
    {
        return $this->configuration->classesWithoutCounterTag;
    }

    public function registerServicesForInMemoryStore(Configuration $messagingConfiguration): void
    {
        $messagingConfiguration->registerServiceDefinition(
            StandardConsistencyBoundaryStrategy::class,
            new Definition(StandardConsistencyBoundaryStrategy::class),
        );
        $messagingConfiguration->registerServiceDefinition(
            DynamicConsistencyBoundaryStrategy::class,
            new Definition(DynamicConsistencyBoundaryStrategy::class),
        );
        $messagingConfiguration->registerServiceDefinition(
            AppendStrategy::class,
            $this->definitionFor(AppendStrategy::class, StandardConsistencyBoundaryStrategy::class, DynamicConsistencyBoundaryStrategy::class),
        );

        $messagingConfiguration->registerServiceDefinition(
            OpenCoreInMemoryTagCollaborator::class,
            new Definition(OpenCoreInMemoryTagCollaborator::class),
        );
        $messagingConfiguration->registerServiceDefinition(
            EnterpriseInMemoryTagCollaborator::class,
            new Definition(EnterpriseInMemoryTagCollaborator::class, [Reference::to(TagResolver::class), Reference::to(LoggingGateway::class)]),
        );
        $messagingConfiguration->registerServiceDefinition(
            InMemoryTagCollaborator::class,
            $this->definitionFor(InMemoryTagCollaborator::class, OpenCoreInMemoryTagCollaborator::class, EnterpriseInMemoryTagCollaborator::class),
        );
    }

    public function definitionFor(string $className, string $openCoreServiceReference, string $enterpriseServiceReference): Definition|Reference
    {
        if (! $this->enabled) {
            return Reference::to($openCoreServiceReference);
        }

        return LicenceDecider::prepareDefinition($className, $openCoreServiceReference, $enterpriseServiceReference);
    }
}
