<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Gateway\GatheredGroups;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

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
final class GatheredGroupOperations implements GatheredGroups
{
    public function __construct(private GatheredGroupStore $store, private EcotoneClockInterface $clock, private int $retention, private ChannelResolver $channelResolver, private MessageBuilder $messageBuilder, private array $gatheredEndpointIds, private array $collectedEndpointIds)
    {
    }

    public function requestRelease(string $groupId): void
    {
        $gatherChannelName = $this->store->gatherChannelNameFor($groupId);
        $trigger = $this->messageBuilder->withPayload($groupId)
            ->setHeader(GatheringHeaders::GROUP_ID, $groupId)
            ->setHeader(GatheringHeaders::CHANNEL_NAME, $gatherChannelName)
            ->setHeader(GatheringHeaders::MANUAL_RELEASE, true)
            ->build();
        $this->channelResolver->resolve($gatherChannelName)->send($trigger);
    }

    public function discard(string $groupId): void
    {
        $this->store->discard($groupId);
    }

    public function cleanupClosedGroups(): void
    {
        $this->store->cleanupClosedGroups($this->clock->now()->unixTime()->inMilliseconds() - $this->retention);
    }

    public function listOpenGroups(string $endpointId): array
    {
        $groups = $this->store->listOpenGroups($endpointId);
        if ($groups === []) {
            $this->refuseUnknownEndpointId($endpointId);
        }

        return $groups;
    }

    public function listClosedGroups(string $endpointId): array
    {
        $groups = $this->store->listClosedGroups($endpointId);
        if ($groups === []) {
            $this->refuseUnknownEndpointId($endpointId);
        }

        return $groups;
    }

    private function refuseUnknownEndpointId(string $endpointId): void
    {
        $known = array_values(array_unique([...array_values($this->gatheredEndpointIds), ...$this->collectedEndpointIds]));
        if (in_array($endpointId, $known, true) || $this->store->listOpenGroups($endpointId) !== [] || $this->store->listClosedGroups($endpointId) !== []) {
            return;
        }
        if (isset($this->gatheredEndpointIds[$endpointId])) {
            $handlerEndpointId = $this->gatheredEndpointIds[$endpointId];
            throw InvalidArgumentException::create("'{$endpointId}' is the gather channel of the gathered handler with endpointId '{$handlerEndpointId}'. Groups are listed by the handler's endpointId: pass '{$handlerEndpointId}'.");
        }
        $knownList = $known === [] ? 'none' : implode(', ', array_map(static fn (string $id): string => "'{$id}'", $known));
        throw InvalidArgumentException::create("No gathered or collected handler has endpointId '{$endpointId}' and no groups are stored under it. Known endpoint ids: {$knownList}. Pass one of them to list its groups.");
    }
}
