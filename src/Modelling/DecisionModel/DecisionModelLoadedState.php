<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use function array_key_exists;
use function array_values;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Modelling\DecisionModel\Snapshot\PendingDecisionModelSnapshot;

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
final class DecisionModelLoadedState
{
    public const HEADER_NAME = 'ecotone.modelling.decision_model.loaded_state';

    /**
     * @param array<string, object|null> $instancesByParameterName
     * @param PendingDecisionModelSnapshot[] $pendingSnapshots
     */
    public function __construct(
        private readonly array $instancesByParameterName,
        private readonly AppendCondition $appendCondition,
        private readonly array $pendingSnapshots = [],
        private readonly int $foldedEventCount = 0,
    ) {
    }

    /**
     * @return class-string[]
     */
    public function loadedModelClassNames(): array
    {
        $classNames = [];
        foreach ($this->instancesByParameterName as $instance) {
            if ($instance !== null) {
                $classNames[$instance::class] = $instance::class;
            }
        }

        return array_values($classNames);
    }

    /**
     * @return array<array{name: string, value: string, expectedVersion: int, decidedBy?: string[]}>
     */
    public function capturedTagVersions(): array
    {
        return $this->appendCondition->expectedTagVersions();
    }

    public function foldedEventCount(): int
    {
        return $this->foldedEventCount;
    }

    /**
     * @return PendingDecisionModelSnapshot[]
     */
    public static function pendingSnapshotsCarriedBy(Message $message): array
    {
        $state = $message->getHeaders()->containsKey(self::HEADER_NAME)
            ? $message->getHeaders()->get(self::HEADER_NAME)
            : null;

        return $state instanceof self ? $state->pendingSnapshots : [];
    }

    public static function appendConditionCarriedBy(Message $message): AppendCondition
    {
        return self::appendConditionIn($message->getHeaders()->headers());
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function appendConditionIn(array $metadata): AppendCondition
    {
        $state = $metadata[self::HEADER_NAME] ?? null;

        return $state instanceof self ? $state->appendCondition : AppendCondition::empty();
    }

    public static function carryInto(Message $message, array $metadata): array
    {
        if ($message->getHeaders()->containsKey(self::HEADER_NAME)) {
            $metadata[self::HEADER_NAME] = $message->getHeaders()->get(self::HEADER_NAME);
        }

        return $metadata;
    }

    public static function instanceCarriedBy(Message $message, string $parameterName): ?object
    {
        $state = $message->getHeaders()->containsKey(self::HEADER_NAME)
            ? $message->getHeaders()->get(self::HEADER_NAME)
            : null;

        if (! $state instanceof self || ! array_key_exists($parameterName, $state->instancesByParameterName)) {
            throw ConfigurationException::create(sprintf(
                "Decision model parameter '%s' was not loaded for this message. Decision models are loaded for #[CommandHandler], #[EventHandler] and #[QueryHandler] methods only.",
                $parameterName,
            ));
        }

        return $state->instancesByParameterName[$parameterName];
    }
}
