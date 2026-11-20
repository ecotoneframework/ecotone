<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\MessageProcessor;
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
final class GatheringSplitterProcessor implements MessageProcessor
{
    public function __construct(
        private MessageProcessor $splitter,
        private ChannelResolver $channelResolver,
        private MessageBuilder $messageBuilder,
        private string $gatherChannelName,
        private string $splitterEndpointId,
        private GatheredGroupStore $store,
        private GatheringTimeouts $timeouts,
        private GatheredHandlerEndpoints $gatheredHandlerEndpoints,
        private EcotoneClockInterface $clock,
    ) {
    }

    public function process(Message $message): ?Message
    {
        $reply = $this->splitter->process($message);
        if ($reply === null) {
            return null;
        }
        $size = count($reply->getPayload());
        $groupId = hash('sha256', $message->getHeaders()->getMessageId() . ':' . $this->splitterEndpointId);
        $timeout = $this->timeouts->forChannel($this->gatherChannelName);
        $deadline = $timeout === null ? null : $this->clock->now()->unixTime()->inMilliseconds() + $timeout;
        $this->store->openGroup(
            groupId: $groupId,
            endpointId: $this->gatheredHandlerEndpoints->forChannel($this->gatherChannelName),
            gatherChannelName: $this->gatherChannelName,
            size: $size,
            original: $message,
            deadline: $deadline,
            whenOpened: function () use ($size, $message, $groupId, $timeout): void {
                if ($size === 0) {
                    $trigger = $this->messageBuilder->fromMessage($this->stamp(message: $message, groupId: $groupId, partNumber: 0, size: 0))
                        ->removeHeader(MessageHeaders::TIME_TO_LIVE)
                        ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                        ->build();
                    $this->channelResolver->resolve($this->gatherChannelName)->send($trigger);
                } elseif ($timeout !== null) {
                    $trigger = $this->messageBuilder->fromMessage($this->stamp(message: $message, groupId: $groupId, partNumber: 0, size: $size))
                        ->removeHeader(MessageHeaders::TIME_TO_LIVE)
                        ->setHeader(GatheringHeaders::TIMEOUT, true)
                        ->setHeader(MessageHeaders::DELIVERY_DELAY, $timeout)
                        ->build();
                    $this->channelResolver->resolve($this->gatherChannelName)->send($trigger);
                }
            },
            groupKey: null,
        );
        if ($size === 0) {
            return $reply;
        }
        $parts = [];
        foreach ($reply->getPayload() as $number => $payload) {
            $part = $payload instanceof Message ? $payload : $this->messageBuilder->fromParentMessage($reply)->setPayload($payload)->setContentType(MediaType::createApplicationXPHP())->build();
            $parts[] = $this->stamp(message: $part, groupId: $groupId, partNumber: $number + 1, size: $size);
        }

        return $this->messageBuilder->fromMessage($reply)->setPayload($parts)->build();
    }

    private function stamp(Message $message, string $groupId, int $partNumber, int $size): Message
    {
        return $this->messageBuilder->fromParentMessage($message)
            ->setHeader(GatheringHeaders::GROUP_ID, $groupId)
            ->setHeader(GatheringHeaders::PART_NUMBER, $partNumber)
            ->setHeader(GatheringHeaders::SIZE, $size)
            ->setHeader(GatheringHeaders::CHANNEL_NAME, $this->gatherChannelName)
            ->build();
    }
}
