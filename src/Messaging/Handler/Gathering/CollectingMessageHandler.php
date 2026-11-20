<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Closure;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\CollectedMessage;
use Ecotone\Api\Messaging\CollectedMessages;
use Ecotone\Api\Messaging\CollectedReleaseReason;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\FrameworkHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Stringable;
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
final class CollectingMessageHandler implements MessageHandler
{
    public function __construct(
        private MessageHandler $handler,
        private GatheredGroupStore $store,
        private MessageBuilder $messageBuilder,
        private ConversionService $conversionService,
        private string $endpointId,
        private string $channelName,
        private ?int $maxSize,
        private AttributeExpressionExecutor $groupBy,
        private CollectedHandlerContext $context,
        private ?int $maxWait,
        private EcotoneClockInterface $clock,
        private ChannelResolver $channelResolver,
        private Type $payloadType,
    ) {
    }

    public function __toString(): string
    {
        return 'Collecting ' . $this->handler;
    }

    public function handle(Message $message): void
    {
        $timeout = $message->getHeaders()->containsKey(GatheringHeaders::TIMEOUT);
        $manualRelease = $message->getHeaders()->containsKey(GatheringHeaders::MANUAL_RELEASE);
        $reason = match (true) {
            $manualRelease => CollectedReleaseReason::MANUAL_RELEASE,
            $timeout => CollectedReleaseReason::MAX_WAIT_REACHED,
            default => CollectedReleaseReason::MAX_SIZE_REACHED,
        };
        $whenReleased = $this->whenReleased($reason);
        if ($timeout || $manualRelease) {
            $this->store->releaseGroup(groupId: $message->getHeaders()->get(GatheringHeaders::GROUP_ID), whenReleased: $whenReleased, releaseReason: $reason);
            return;
        }
        $this->store->collectMessage(
            endpointId: $this->endpointId,
            groupKey: $this->groupKeyFor($message),
            gatherChannelName: $this->channelName,
            maxSize: $this->maxSize,
            message: $this->recordedFormOf($message),
            deadline: $this->maxWait === null ? null : $this->clock->now()->unixTime()->inMilliseconds() + $this->maxWait,
            whenOpened: fn (string $groupId) => $this->scheduleTimeout($groupId, $message),
            whenReleased: $whenReleased,
        );
    }

    private function whenReleased(CollectedReleaseReason $reason): Closure
    {
        return function (Message $original, array $parts, ?int $size, ?string $groupKey) use ($reason): void {
            $this->invokeHandler(firstMessage: $original, parts: $parts, groupKey: $groupKey, reason: $reason);
        };
    }

    private function invokeHandler(Message $firstMessage, array $parts, ?string $groupKey, CollectedReleaseReason $reason): void
    {
        ksort($parts);
        $messages = [];
        foreach ($parts as $part) {
            $messages[] = new CollectedMessage(payload: $this->payloadOf($part), headers: $part->getHeaders()->headers());
        }
        $batch = new CollectedMessages(messages: $messages, groupKey: $groupKey, releaseReason: $reason);
        $this->handler->handle($this->messageBuilder->withPayload($batch)->setMultipleHeaders($this->contextOf($firstMessage))->setContentType(MediaType::createApplicationXPHPWithTypeParameter(CollectedMessages::class))->build());
    }

    private function groupKeyFor(Message $message): ?string
    {
        if (! $this->groupBy->hasExpression()) {
            return null;
        }
        try {
            $key = $this->groupBy->execute($this->messageBuilder->fromMessage($message)->setPayload($this->payloadOf($message))->setContentType(MediaType::createApplicationXPHP())->build());
            $groupKey = is_string($key) || is_int($key) || $key instanceof Stringable ? (string) $key : null;
        } catch (Throwable $failure) {
            throw InvalidArgumentException::createFromPreviousException("Collected handler {$this->context->description} groupBy '{$this->context->groupByDescription}' failed to evaluate. Correct groupBy or the message payload/headers, or remove groupBy to collect every message together.", $failure);
        }
        if ($groupKey === null || $groupKey === '') {
            throw InvalidArgumentException::create("Collected handler {$this->context->description} groupBy '{$this->context->groupByDescription}' must return a non-empty string, an integer or a stringable object. Correct groupBy or the message payload/headers, or remove groupBy to collect every message together.");
        }
        return $groupKey;
    }

    private function scheduleTimeout(string $groupId, Message $firstMessage): void
    {
        if ($this->maxWait === null) {
            return;
        }
        $trigger = $this->messageBuilder->withPayload($groupId)
            ->setMultipleHeaders($this->contextOf($firstMessage))
            ->setHeader(GatheringHeaders::GROUP_ID, $groupId)
            ->setHeader(GatheringHeaders::TIMEOUT, true)
            ->setHeader(MessageHeaders::DELIVERY_DELAY, $this->maxWait)
            ->build();
        $this->channelResolver->resolve($this->channelName)->send($trigger);
    }

    private function recordedFormOf(Message $message): Message
    {
        $headers = $message->getHeaders()->headers();
        $recorded = FrameworkHeaders::unsetEnqueueMetadata(FrameworkHeaders::unsetAggregateKeys($headers));
        if (array_key_exists(MessageHeaders::CONTENT_TYPE, $headers)) {
            $recorded[MessageHeaders::CONTENT_TYPE] = $headers[MessageHeaders::CONTENT_TYPE];
        }

        return $this->messageBuilder->fromMessage($message)->removeHeaders(array_keys(array_diff_key($headers, $recorded)))->build();
    }

    private function contextOf(Message $message): array
    {
        return GatheringMessageContext::from($message);
    }

    private function payloadOf(Message $message): mixed
    {
        $mediaType = $message->getHeaders()->hasContentType() ? $message->getHeaders()->getContentType() : MediaType::createApplicationXPHP();
        $type = $this->payloadType->isAnything() && $message->getHeaders()->containsKey(MessageHeaders::TYPE_ID) ? Type::create($message->getHeaders()->get(MessageHeaders::TYPE_ID)) : $this->payloadType;
        if ($mediaType->isCompatibleWith(MediaType::createApplicationXPHP()) && $type->accepts($message->getPayload())) {
            return $message->getPayload();
        }
        return $this->conversionService->convert(source: $message->getPayload(), sourcePHPType: Type::createFromVariable($message->getPayload()), sourceMediaType: $mediaType, targetPHPType: $type, targetMediaType: MediaType::createApplicationXPHP());
    }
}
