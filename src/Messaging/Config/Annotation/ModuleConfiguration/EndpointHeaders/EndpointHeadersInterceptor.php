<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use DateTimeImmutable;
use DateTimeInterface;
use Ecotone\Messaging\Attribute\Endpoint\AddHeader;
use Ecotone\Messaging\Attribute\Endpoint\ContentType;
use Ecotone\Messaging\Attribute\Endpoint\Delayed;
use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Messaging\Attribute\Endpoint\RemoveHeader;
use Ecotone\Messaging\Attribute\Endpoint\TimeToLive;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutor;
use Ecotone\Messaging\Handler\ClosureExpression\ExecutorFor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\Type\UnionType;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\TimeSpan;

use function is_string;
use function preg_match;
use function str_ends_with;

/**
 * Class EndpointHeadersInterceptor
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class EndpointHeadersInterceptor implements DefinedObject
{
    public function addMetadata(
        Message $message,
        #[ExecutorFor(AddHeader::class)] ?AttributeExpressionExecutor $addHeader,
        #[ExecutorFor(Delayed::class)] ?AttributeExpressionExecutor $delayed,
        #[ExecutorFor(Priority::class)] ?AttributeExpressionExecutor $priority,
        #[ExecutorFor(TimeToLive::class)] ?AttributeExpressionExecutor $timeToLive,
        #[ExecutorFor(RemoveHeader::class)] ?AttributeExpressionExecutor $removeHeader,
        #[ExecutorFor(ContentType::class)] ?AttributeExpressionExecutor $contentType,
    ): array {
        $metadata = [];

        if ($addHeader) {
            /** @var AddHeader $addHeaderAttribute */
            $addHeaderAttribute = $addHeader->getAttribute();
            $metadata[$addHeaderAttribute->getHeaderName()] = $this->headerValueOf($addHeader, $message);
        }

        $isContentTypeHeaderExists = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE);
        if ($contentType) {
            /** @var ContentType $contentTypeAttribute */
            $contentTypeAttribute = $contentType->getAttribute();
            if ($contentTypeAttribute->shouldReplaceExistingHeader() || ! $isContentTypeHeaderExists) {
                $metadata[MessageHeaders::CONTENT_TYPE] = $contentTypeAttribute->getHeaderValue();
            } else {
                $metadata[MessageHeaders::CONTENT_TYPE] = $message->getHeaders()->get(MessageHeaders::CONTENT_TYPE);
            }
        }

        /** @var Delayed|null $delayedAttribute */
        $delayedAttribute = $delayed?->getAttribute();
        $isDeliveryDelayHeaderExists = $message->getHeaders()->containsKey(MessageHeaders::DELIVERY_DELAY);
        if ($delayedAttribute && ($delayedAttribute->shouldReplaceExistingHeader() || ! $isDeliveryDelayHeaderExists)) {
            $metadata[MessageHeaders::DELIVERY_DELAY] = $this->headerValueOf($delayed, $message);

            if (is_string($metadata[MessageHeaders::DELIVERY_DELAY])) {
                $metadata[MessageHeaders::DELIVERY_DELAY] = $this->parseDateTimeStringWithRequiredOffset($metadata[MessageHeaders::DELIVERY_DELAY]);
            }

            $type = Type::createFromVariable($metadata[MessageHeaders::DELIVERY_DELAY]);
            if (! $type->isCompatibleWith(UnionType::createWith([
                Type::int(),
                Type::object(TimeSpan::class),
                Type::object(DateTimeInterface::class),
            ]))) {
                throw ConfigurationException::create("Delivery delay should be either integer, TimeSpan or DateTimeInterface, but got {$type->toString()}");
            }
        } elseif ($isDeliveryDelayHeaderExists) {
            $metadata[MessageHeaders::DELIVERY_DELAY] = $message->getHeaders()->get(MessageHeaders::DELIVERY_DELAY);
        }

        if ($priority) {
            $metadata[MessageHeaders::PRIORITY] = $this->headerValueOf($priority, $message);
        }

        /** @var TimeToLive|null $timeToLiveAttribute */
        $timeToLiveAttribute = $timeToLive?->getAttribute();
        $isTtlHeaderExists = $message->getHeaders()->containsKey(MessageHeaders::TIME_TO_LIVE);
        if ($timeToLiveAttribute && ($timeToLiveAttribute->shouldReplaceExistingHeader() || ! $isTtlHeaderExists)) {
            $metadata[MessageHeaders::TIME_TO_LIVE] = $this->headerValueOf($timeToLive, $message);

            $type = Type::createFromVariable($metadata[MessageHeaders::TIME_TO_LIVE]);
            if (! $type->isCompatibleWith(UnionType::createWith([
                Type::int(),
                Type::object(TimeSpan::class),
            ]))) {
                throw ConfigurationException::create("Delivery delay should be either integer or TimeSpan, but got {$type->toString()}");
            }
        } elseif ($isTtlHeaderExists) {
            $metadata[MessageHeaders::TIME_TO_LIVE] = $message->getHeaders()->get(MessageHeaders::TIME_TO_LIVE);
        }

        if ($removeHeader) {
            /** @var RemoveHeader $removeHeaderAttribute */
            $removeHeaderAttribute = $removeHeader->getAttribute();
            $metadata[$removeHeaderAttribute->getHeaderName()] = null;
        }

        return $metadata;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }

    private function headerValueOf(AttributeExpressionExecutor $executor, Message $message): mixed
    {
        if ($executor->hasExpression()) {
            return $executor->execute($message);
        }

        /** @var AddHeader $attribute */
        $attribute = $executor->getAttribute();

        return $attribute->getHeaderValue();
    }

    private function parseDateTimeStringWithRequiredOffset(string $dateTimeString): DateTimeImmutable
    {
        if (! $this->hasUtcOffset($dateTimeString)) {
            throw ConfigurationException::create("Delivery delay string '{$dateTimeString}' must contain a UTC offset (e.g., '+02:00' or 'Z'). Dates without timezone information are ambiguous.");
        }

        return new DateTimeImmutable($dateTimeString);
    }

    private function hasUtcOffset(string $dateTimeString): bool
    {
        return preg_match('/[+-]\d{2}:\d{2}$/', $dateTimeString) === 1
            || preg_match('/[+-]\d{4}$/', $dateTimeString) === 1
            || str_ends_with($dateTimeString, 'Z');
    }
}
