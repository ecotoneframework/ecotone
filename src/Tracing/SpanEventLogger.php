<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use function array_filter;

use const ARRAY_FILTER_USE_KEY;

use Ecotone\Api\Messaging\MessageHeaders;

use function in_array;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use Throwable;

/**
 * licence Apache-2.0
 */
final class SpanEventLogger implements LoggerInterface
{
    use LoggerTrait;

    public function __construct(private Tracer $tracer)
    {
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $currentSpan = $this->tracer->currentSpan();
        if ($currentSpan === null) {
            return;
        }

        $attributes = array_filter(
            $context,
            fn (string|int $key): bool => in_array($key, [MessageHeaders::MESSAGE_ID, MessageHeaders::MESSAGE_CORRELATION_ID, MessageHeaders::PARENT_MESSAGE_ID], true),
            ARRAY_FILTER_USE_KEY
        );
        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $attributes['exception.type'] = $context['exception']::class;
            $attributes['exception.message'] = $context['exception']->getMessage();
            $attributes['exception.stacktrace'] = $context['exception']->getTraceAsString();
        }

        $currentSpan->addEvent((string) $message, $attributes);
    }
}
