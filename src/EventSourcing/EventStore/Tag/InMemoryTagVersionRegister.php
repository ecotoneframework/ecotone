<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore\Tag;

use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
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
final class InMemoryTagVersionRegister
{
    /**
     * @var array<string, int>
     */
    private array $versions = [];

    public function __construct(private readonly LoggingGateway $logger)
    {
    }

    /**
     * @param array<string, array{name: string, value: string}> $tags
     * @return array<string, array{name: string, value: string, expectedVersion: int}>
     */
    public function capture(array $tags): array
    {
        $captured = [];
        foreach ($tags as $key => $tag) {
            $captured[$key] = ['name' => $tag['name'], 'value' => $tag['value'], 'expectedVersion' => $this->versions[$key] ?? 0];
        }

        return $captured;
    }

    /**
     * @param array<string, array{name: string, value: string, expectedVersion: int, aggregateType?: string, decidedBy?: string[]}> $expectedVersions
     */
    public function assertUnchanged(array $expectedVersions): void
    {
        foreach ($expectedVersions as $key => $expected) {
            $current = $this->versions[$key] ?? 0;
            if ($current !== $expected['expectedVersion']) {
                $conflict = isset($expected['aggregateType'])
                    ? DecisionModelConcurrencyException::forAggregateConflict($expected['aggregateType'], $expected['value'], $expected['expectedVersion'], $current)
                    : DecisionModelConcurrencyException::forConflict($expected['name'], $expected['value'], $expected['expectedVersion'], $current, $expected['decidedBy'] ?? []);

                $this->logger->notice(DecisionModelConcurrencyException::LOG_MESSAGE, $conflict->conflictFields());

                throw $conflict;
            }
        }
    }

    /**
     * @param array<string, array{name: string, value: string, expectedVersion: int}> $expectedVersions
     */
    public function bump(array $expectedVersions): void
    {
        foreach (array_keys($expectedVersions) as $key) {
            $this->versions[$key] = ($this->versions[$key] ?? 0) + 1;
        }
    }
}
