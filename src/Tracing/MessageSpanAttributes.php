<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Messaging\Support\FrameworkHeaders;
use Ecotone\Projecting\ProjectingHeaders;

use function is_scalar;

/**
 * licence Apache-2.0
 */
final class MessageSpanAttributes
{
    private const ALWAYS_REPORTED = [ProjectingHeaders::REBUILD_BATCH_NUMBER, ProjectingHeaders::REBUILD_BATCH_COUNT, ProjectingHeaders::REBUILD_PARTITION_COUNT];
    private const RESERVED_PREFIXES = ['ecotone.', 'messaging.', 'code.'];

    /**
     * @param string[] $headerNames
     */
    public function __construct(
        private bool $fromAllHeaders,
        private array $headerNames,
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function from(Message $message): array
    {
        $attributes = [];
        foreach (FrameworkHeaders::unsetAllFrameworkHeaders($message->getHeaders()->headers()) as $key => $value) {
            if (is_scalar($value) && $this->isReported((string) $key)) {
                $attributes[$key] = $value;
            }
        }

        $headers = $message->getHeaders();
        $attributes[MessageHeaders::MESSAGE_ID] = $headers->getMessageId();
        $attributes[MessageHeaders::MESSAGE_CORRELATION_ID] = $headers->getCorrelationId();
        if ($headers->getParentId() !== null) {
            $attributes[MessageHeaders::PARENT_MESSAGE_ID] = $headers->getParentId();
        }
        $attributes[MessageHeaders::TIMESTAMP] = $headers->getTimestamp();
        $attributes[SpanAttribute::MESSAGING_SYSTEM] = SpanAttribute::ECOTONE;

        return $attributes;
    }

    private function isReported(string $headerName): bool
    {
        if (in_array($headerName, self::ALWAYS_REPORTED, true)) {
            return true;
        }
        foreach (self::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($headerName, $prefix)) {
                return false;
            }
        }

        return $this->fromAllHeaders || in_array($headerName, $this->headerNames, true);
    }
}
