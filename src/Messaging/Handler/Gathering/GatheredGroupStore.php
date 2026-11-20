<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Messaging\CollectedReleaseReason;
use Ecotone\Api\Messaging\GatheredReleaseReason;
use Ecotone\Api\Messaging\Message;

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
interface GatheredGroupStore
{
    public const UNKNOWN_SIZE = -1;

    public function collectMessage(string $endpointId, ?string $groupKey, string $gatherChannelName, ?int $maxSize, Message $message, ?int $deadline, callable $whenOpened, callable $whenReleased): void;

    public function openGroup(string $groupId, string $endpointId, string $gatherChannelName, int $size, Message $original, ?int $deadline, callable $whenOpened, ?string $groupKey): void;

    public function completePart(string $groupId, int $partNumber, Message $report, callable $whenComplete): void;

    public function gatherChannelNameFor(string $groupId): string;

    public function releaseGroup(string $groupId, callable $whenReleased, GatheredReleaseReason|CollectedReleaseReason|null $releaseReason): void;

    public function discard(string $groupId): void;

    public function cleanupClosedGroups(int $closedAtOrBefore): void;

    /** @return list<array{groupId: string, endpointId: string, groupKey: ?string, state: string, expectedSize: ?int, recorded: int, missingPartNumbers: list<int>, latePartCount: int, openedAt: int, deadline: ?int, closedAt: ?int, releaseReason: ?string}> */
    public function listOpenGroups(string $endpointId): array;

    /** @return list<array{groupId: string, endpointId: string, groupKey: ?string, state: string, expectedSize: ?int, recorded: int, missingPartNumbers: list<int>, latePartCount: int, openedAt: int, deadline: ?int, closedAt: ?int, releaseReason: ?string}> */
    public function listClosedGroups(string $endpointId): array;
}
