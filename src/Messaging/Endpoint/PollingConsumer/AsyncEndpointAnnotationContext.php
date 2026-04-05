<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

/**
 * licence Enterprise
 */
class AsyncEndpointAnnotationContext
{
    /** @var object[] */
    private array $currentAnnotations = [];

    /**
     * @param object[] $annotations
     */
    public function setAnnotations(array $annotations): void
    {
        $this->currentAnnotations = $annotations;
    }

    /**
     * @return object[]
     */
    public function getCurrentAnnotations(): array
    {
        return $this->currentAnnotations;
    }

    public function clear(): void
    {
        $this->currentAnnotations = [];
    }
}
