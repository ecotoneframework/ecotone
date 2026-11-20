<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\Gathering\GatheredGroupStore;
use Ecotone\Messaging\Handler\Gathering\GatheringHeaders;
use Ecotone\Messaging\Support\FrameworkHeaders;

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
final class GatheredPartitionBatchDispatcher implements PartitionBatchDispatcher
{
    public function __construct(
        private UngatheredPartitionBatchDispatcher $ungathered,
        private GatheredGroupStore $store,
        private MessagingEntrypointService $messagingEntrypoint,
    ) {
    }

    public function dispatch(string $projectionName, string $operation, ?string $asyncChannelName, Message $prepare, array $batches): void
    {
        if ($asyncChannelName === null) {
            $this->ungathered->dispatch(projectionName: $projectionName, operation: $operation, asyncChannelName: null, prepare: $prepare, batches: $batches);

            return;
        }
        $endpointId = ProjectionRun::endpointIdFor($projectionName, $operation);
        $gatherChannelName = ProjectionRun::gatherChannelFor($projectionName, $operation);
        $groupId = hash('sha256', $prepare->getHeaders()->getMessageId() . ':' . $endpointId);
        $size = count($batches);
        $this->store->openGroup(
            groupId: $groupId,
            endpointId: $endpointId,
            gatherChannelName: $gatherChannelName,
            size: $size,
            original: $prepare,
            deadline: null,
            whenOpened: function () use ($size, $projectionName, $groupId, $gatherChannelName, $prepare): void {
                if ($size === 0) {
                    $this->messagingEntrypoint->sendWithHeaders(payload: $projectionName, headers: [...FrameworkHeaders::unsetHeadersNotPropagatedToFollowingMessages($prepare->getHeaders()->headers()), ...self::stamp(groupId: $groupId, partNumber: 0, size: 0, gatherChannelName: $gatherChannelName)], targetChannel: $gatherChannelName);
                }
            },
            groupKey: $prepare->getHeaders()->containsKey(MessageHeaders::TRACING_ID) ? (string) $prepare->getHeaders()->get(MessageHeaders::TRACING_ID) : null,
        );
        foreach ($batches as $index => $headers) {
            $this->ungathered->send(
                projectionName: $projectionName,
                headers: [...$headers, ...self::stamp(groupId: $groupId, partNumber: $index + 1, size: $size, gatherChannelName: $gatherChannelName)],
                asyncChannelName: $asyncChannelName,
                prepare: $prepare,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function stamp(string $groupId, int $partNumber, int $size, string $gatherChannelName): array
    {
        return [
            GatheringHeaders::GROUP_ID => $groupId,
            GatheringHeaders::PART_NUMBER => $partNumber,
            GatheringHeaders::SIZE => $size,
            GatheringHeaders::CHANNEL_NAME => $gatherChannelName,
        ];
    }
}
