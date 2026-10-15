<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use function array_keys;

use Ecotone\Messaging\Config\ConfigurationException;

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
final class DecisionModelDefinitionRegistry
{
    /**
     * @param array<class-string, array{tagNames: string[], literalTagValues: array<string, string>, handledEventClasses: class-string[], aggregate?: array{className: class-string, aggregateType: string, streamName: string, identifierNames: string[]}}> $rawDefinitions
     */
    private function __construct(
        private readonly array $rawDefinitions,
    ) {
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param array<class-string, array{tagNames: string[], literalTagValues: array<string, string>, handledEventClasses: class-string[], aggregate?: array{className: class-string, aggregateType: string, streamName: string, identifierNames: string[]}}> $rawDefinitions
     */
    public static function createWith(array $rawDefinitions): self
    {
        return new self($rawDefinitions);
    }

    public function has(string $className): bool
    {
        return isset($this->rawDefinitions[$className]);
    }

    public function get(string $className): DecisionModelDefinition
    {
        $raw = $this->rawDefinitionFor($className);

        return new DecisionModelDefinition($className, $raw['tagNames'], $raw['handledEventClasses'], $raw['literalTagValues']);
    }

    public function getAggregateBacked(string $className): AggregateBackedDecisionModelDefinition
    {
        $raw = $this->rawDefinitionFor($className);
        $aggregate = $raw['aggregate'] ?? throw ConfigurationException::create(sprintf(
            'DecisionModel %s is not backed by an aggregate.',
            $className
        ));

        return new AggregateBackedDecisionModelDefinition(
            $className,
            $aggregate['className'],
            $aggregate['aggregateType'],
            $aggregate['streamName'],
            $aggregate['identifierNames'],
            $raw['handledEventClasses'],
        );
    }

    /**
     * @return array{tagNames: string[], literalTagValues: array<string, string>, handledEventClasses: class-string[], aggregate?: array{className: class-string, aggregateType: string, streamName: string, identifierNames: string[]}}
     */
    private function rawDefinitionFor(string $className): array
    {
        return $this->rawDefinitions[$className] ?? throw ConfigurationException::create(sprintf(
            'No DecisionModel definition found for %s. Did you forget #[DecisionModel]?',
            $className
        ));
    }

    /**
     * @return class-string[]
     */
    public function classNames(): array
    {
        return array_keys($this->rawDefinitions);
    }
}
