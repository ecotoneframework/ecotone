<?php

declare(strict_types=1);

namespace Ecotone\Api\Gateway;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
interface GatheredGroups
{
    /** @return list<array{groupId: string, endpointId: string, groupKey: ?string, state: string, expectedSize: ?int, recorded: int, missingPartNumbers: list<int>, latePartCount: int, openedAt: int, deadline: ?int, closedAt: ?int, releaseReason: ?string}> */
    public function listOpenGroups(string $endpointId): array;

    /** @return list<array{groupId: string, endpointId: string, groupKey: ?string, state: string, expectedSize: ?int, recorded: int, missingPartNumbers: list<int>, latePartCount: int, openedAt: int, deadline: ?int, closedAt: ?int, releaseReason: ?string}> */
    public function listClosedGroups(string $endpointId): array;

    public function requestRelease(string $groupId): void;

    public function discard(string $groupId): void;

    public function cleanupClosedGroups(): void;
}
