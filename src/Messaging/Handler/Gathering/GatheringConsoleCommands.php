<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Console\ConsoleCommandResultSet;
use Ecotone\Api\Gateway\GatheredGroups;
use Ecotone\Api\Scheduling\DatePoint;

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
 *
 */
final class GatheringConsoleCommands
{
    public function __construct(private GatheredGroups $groups)
    {
    }

    public function requestRelease(string $groupId): ConsoleCommandResultSet
    {
        $this->groups->requestRelease($groupId);

        return ConsoleCommandResultSet::create(['Group id', 'Status'], [[$groupId, 'release requested']]);
    }

    public function discard(string $groupId): ConsoleCommandResultSet
    {
        $this->groups->discard($groupId);

        return ConsoleCommandResultSet::create(['Group id', 'Status'], [[$groupId, 'discarded']]);
    }

    public function listGroups(string $endpointId, bool $closed = false): ConsoleCommandResultSet
    {
        $groups = $closed ? $this->groups->listClosedGroups($endpointId) : $this->groups->listOpenGroups($endpointId);

        return ConsoleCommandResultSet::create(
            columnHeaders: ['Group id', 'Endpoint id', 'Group key', 'State', 'Expected size', 'Recorded', 'Missing parts', 'Late parts', 'Opened at', 'Deadline', 'Closed at', 'Release reason'],
            rows: array_map(static fn (array $group): array => [
                $group['groupId'],
                $group['endpointId'],
                $group['groupKey'] ?? '',
                $group['state'],
                $group['expectedSize'] ?? 'no expected size',
                $group['recorded'],
                implode(', ', $group['missingPartNumbers']),
                $group['latePartCount'],
                self::formatted($group['openedAt']),
                $group['deadline'] === null ? 'no deadline' : self::formatted($group['deadline']),
                $group['closedAt'] === null ? '' : self::formatted($group['closedAt']),
                $group['releaseReason'] ?? '',
            ], $groups),
        );
    }

    private static function formatted(int $milliseconds): string
    {
        return DatePoint::createFromTimestamp($milliseconds / 1000)->format(DATE_ATOM);
    }
}
