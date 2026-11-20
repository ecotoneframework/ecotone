<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\CollectedReleaseReason;
use Ecotone\Api\Messaging\ConcurrencyException;
use Ecotone\Api\Messaging\GatheredReleaseReason;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Throwable;

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
final class InMemoryGatheredGroupStore implements GatheredGroupStore
{
    private array $groups = [];

    public function __construct(private EcotoneClockInterface $clock, private MessageBuilder $messageBuilder)
    {
    }

    public function listOpenGroups(string $endpointId): array
    {
        return $this->listGroups(endpointId: $endpointId, closed: false);
    }

    public function listClosedGroups(string $endpointId): array
    {
        return $this->listGroups(endpointId: $endpointId, closed: true);
    }

    private function listGroups(string $endpointId, bool $closed): array
    {
        return array_values(array_map(static fn (array $group): array => [
            'groupId' => $group['groupId'],
            'endpointId' => $group['endpointId'],
            'groupKey' => $group['groupKey'],
            'state' => $group['state'],
            'expectedSize' => GatheredGroupSize::asNullable($group['size']),
            'recorded' => count($group['parts']),
            'missingPartNumbers' => ! $group['collected'] && GatheredGroupSize::expectsNumberedParts($group['size']) ? array_values(array_diff(range(1, $group['size']), array_keys($group['parts']))) : [],
            'latePartCount' => $group['latePartCount'],
            'openedAt' => $group['openedAt'],
            'deadline' => $group['deadline'],
            'closedAt' => $group['closedAt'],
            'releaseReason' => $group['releaseReason'],
        ], array_filter($this->groups, static fn (array $group): bool => $group['endpointId'] === $endpointId && ($closed ? $group['state'] !== 'open' : $group['state'] === 'open'))));
    }

    public function collectMessage(string $endpointId, ?string $groupKey, string $gatherChannelName, ?int $maxSize, Message $message, ?int $deadline, callable $whenOpened, callable $whenReleased): void
    {
        $groupId = null;
        foreach ($this->groups as $id => $group) {
            if ($group['endpointId'] !== $endpointId || ! $group['collected']) {
                continue;
            }
            foreach ($group['parts'] as $part) {
                if ($part->getHeaders()->getMessageId() === $message->getHeaders()->getMessageId()) {
                    return;
                }
            }
            if ($group['groupKey'] === $groupKey && $group['openSlot'] === 1) {
                $groupId = $id;
            }
        }
        $previousGroups = $this->groups;
        try {
            if ($groupId === null) {
                $groupId = bin2hex(random_bytes(32));
                $this->openGroup(groupId: $groupId, endpointId: $endpointId, gatherChannelName: $gatherChannelName, size: $maxSize ?? GatheredGroupStore::UNKNOWN_SIZE, original: $message, deadline: $deadline, whenOpened: static fn () => null, groupKey: null);
                $this->groups[$groupId]['collected'] = true;
                $this->groups[$groupId]['groupKey'] = $groupKey;
                $this->groups[$groupId]['openSlot'] = 1;
                $whenOpened($groupId);
            }
            $this->completePart(groupId: $groupId, partNumber: count($this->groups[$groupId]['parts']) + 1, report: $message, whenComplete: $whenReleased);
        } catch (Throwable $failure) {
            $this->groups = $previousGroups;
            throw $failure;
        }
    }

    public function openGroup(string $groupId, string $endpointId, string $gatherChannelName, int $size, Message $original, ?int $deadline, callable $whenOpened, ?string $groupKey): void
    {
        if (isset($this->groups[$groupId])) {
            return;
        }
        $this->groups[$groupId] = [
            'original' => $this->messageBuilder->fromMessage($original)->setPayload(unserialize(serialize($original->getPayload())))->build(),
            'parts' => [],
            'version' => 0,
            'state' => 'open',
            'latePartCount' => 0,
            'closedAt' => null,
            'deadline' => $deadline,
            'groupId' => $groupId,
            'endpointId' => $endpointId,
            'gatherChannelName' => $gatherChannelName,
            'size' => $size,
            'collected' => false,
            'groupKey' => $groupKey,
            'openSlot' => null,
            'openedAt' => $this->clock->now()->unixTime()->inMilliseconds(),
            'releaseReason' => null,
        ];
        try {
            $whenOpened();
        } catch (Throwable $failure) {
            unset($this->groups[$groupId]);
            throw $failure;
        }
    }

    public function completePart(string $groupId, int $partNumber, Message $report, callable $whenComplete): void
    {
        $previous = $this->groups[$groupId] ?? throw GatheredGroupNotFound::forId($groupId);
        if ($previous['state'] !== 'open') {
            if (GatheredGroupSize::expectsNumberedParts($previous['size']) && ! isset($previous['parts'][$partNumber])) {
                $this->groups[$groupId]['latePartCount']++;
                $this->groups[$groupId]['version']++;
            }

            return;
        }
        if (isset($previous['parts'][$partNumber])) {
            return;
        }
        if ($this->groups[$groupId]['version'] !== $previous['version']) {
            throw ConcurrencyException::forGatheredGroup($groupId);
        }
        $this->groups[$groupId]['version']++;
        if (GatheredGroupSize::expectsNumberedParts($previous['size']) || $previous['collected']) {
            $this->groups[$groupId]['parts'][$partNumber] = $report;
        }
        if (count($this->groups[$groupId]['parts']) !== $previous['size']) {
            return;
        }
        $this->groups[$groupId]['state'] = 'released';
        $this->groups[$groupId]['openSlot'] = null;
        $this->groups[$groupId]['releaseReason'] = $previous['collected'] ? CollectedReleaseReason::MAX_SIZE_REACHED->value : GatheredReleaseReason::ALL_PARTS_ACCOUNTED_FOR->value;
        $this->groups[$groupId]['closedAt'] = $this->clock->now()->unixTime()->inMilliseconds();
        try {
            $whenComplete($previous['original'], $this->groups[$groupId]['parts'], GatheredGroupSize::asNullable($previous['size']), $previous['groupKey']);
        } catch (Throwable $failure) {
            $this->groups[$groupId] = $previous;
            throw $failure;
        }
    }

    public function gatherChannelNameFor(string $groupId): string
    {
        $group = $this->groups[$groupId] ?? throw GatheredGroupNotFound::forId($groupId);

        return $group['gatherChannelName'];
    }

    public function releaseGroup(string $groupId, callable $whenReleased, GatheredReleaseReason|CollectedReleaseReason|null $releaseReason): void
    {
        $previous = $this->groups[$groupId] ?? null;
        if ($previous === null || $previous['state'] !== 'open') {
            return;
        }
        if ($this->groups[$groupId]['version'] !== $previous['version']) {
            throw ConcurrencyException::forGatheredGroup($groupId);
        }
        $this->groups[$groupId]['version']++;
        $this->groups[$groupId]['state'] = 'released';
        $this->groups[$groupId]['openSlot'] = null;
        $this->groups[$groupId]['releaseReason'] = $releaseReason?->value;
        $this->groups[$groupId]['closedAt'] = $this->clock->now()->unixTime()->inMilliseconds();
        try {
            $whenReleased($previous['original'], $previous['parts'], GatheredGroupSize::asNullable($previous['size']), $previous['groupKey']);
        } catch (Throwable $failure) {
            $this->groups[$groupId] = $previous;
            throw $failure;
        }
    }

    public function discard(string $groupId): void
    {
        $group = $this->groups[$groupId] ?? throw GatheredGroupNotFound::forId($groupId);
        if ($group['state'] !== 'open') {
            return;
        }
        $this->groups[$groupId]['version']++;
        $this->groups[$groupId]['state'] = 'discarded';
        $this->groups[$groupId]['openSlot'] = null;
        $this->groups[$groupId]['closedAt'] = $this->clock->now()->unixTime()->inMilliseconds();
    }

    public function cleanupClosedGroups(int $closedAtOrBefore): void
    {
        $this->groups = array_filter($this->groups, static fn (array $group): bool => $group['state'] === 'open' || $group['closedAt'] > $closedAtOrBefore);
    }
}
