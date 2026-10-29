<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

/**
 * licence Enterprise
 */
class AsyncHandlerAnnotationRegistry
{
    /**
     * @param array<string, object[]> $channelToAnnotations
     */
    public function __construct(private array $channelToAnnotations)
    {
    }

    /**
     * @return object[]
     */
    public function getAnnotationsForChannel(string $channel): array
    {
        return $this->channelToAnnotations[$channel] ?? [];
    }
}
