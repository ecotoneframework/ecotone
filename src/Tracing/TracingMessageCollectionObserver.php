<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Messaging\Channel\Collector\MessageCollectionObserver;

/**
 * licence Apache-2.0
 */
final class TracingMessageCollectionObserver implements MessageCollectionObserver
{
    public function __construct(private Tracer $tracer)
    {
    }

    public function collected(Message $message): void
    {
        $span = $this->tracer->currentSpan();
        if ($span !== null && str_starts_with($span->name(), TracedRouteProcessor::SPAN_NAME_PREFIX)) {
            $span->setAttribute(SpanAttribute::ROUTE_COLLECTED, true);
        }
    }
}
