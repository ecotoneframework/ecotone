<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Modelling\DecisionModel\DecisionModelBatchLoader;
use Ecotone\Modelling\DecisionModel\DecisionModelLoadedState;

use function explode;

use Throwable;

/**
 * licence Apache-2.0
 */
final class TracedDecisionModelBatchLoader
{
    public function __construct(
        private readonly DecisionModelBatchLoader $batchLoader,
        private readonly string $handlerName,
        private readonly Tracer $tracer,
        private readonly MessageSpanAttributes $messageSpanAttributes,
    ) {
    }

    /**
     * @return array<string, DecisionModelLoadedState>
     */
    public function load(Message $message): array
    {
        [$class, $method] = explode('::', $this->handlerName, 2);
        $span = $this->tracer->startSpan('Decision Models: ' . explode("\0", $class, 2)[0] . '::' . $method, SpanKind::Internal, $this->messageSpanAttributes->from($message));

        try {
            $loadedState = $this->batchLoader->load($message);
        } catch (Throwable $failure) {
            $span->fail($failure);

            throw $failure;
        }

        $state = $loadedState[DecisionModelLoadedState::HEADER_NAME];
        $capturedTagVersions = $state->capturedTagVersions();
        $span->setAttribute(DynamicConsistencyBoundarySpanAttributes::MODELS, DynamicConsistencyBoundarySpanAttributes::classList($state->loadedModelClassNames()));
        $span->setAttribute(DynamicConsistencyBoundarySpanAttributes::TAGS, DynamicConsistencyBoundarySpanAttributes::userTags($capturedTagVersions));
        $span->setAttribute(DynamicConsistencyBoundarySpanAttributes::CAPTURED_VERSIONS, DynamicConsistencyBoundarySpanAttributes::capturedVersions($capturedTagVersions));
        $span->setAttribute(DynamicConsistencyBoundarySpanAttributes::AGGREGATES, DynamicConsistencyBoundarySpanAttributes::aggregates($capturedTagVersions));
        $span->setAttribute(DynamicConsistencyBoundarySpanAttributes::EVENTS_FOLDED, $state->foldedEventCount());
        $span->end();

        return $loadedState;
    }
}
