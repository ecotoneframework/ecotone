<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\InMemory;

use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\PrecedenceChannelInterceptor;

final class InMemoryQueueAcknowledgeInterceptorBuilder implements ChannelInterceptorBuilder
{
    public function __construct(private string $relatedChannel)
    {
    }

    public function relatedChannelName(): string
    {
        return $this->relatedChannel;
    }

    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [];
    }

    public function getPrecedence(): int
    {
        return PrecedenceChannelInterceptor::DEFAULT_PRECEDENCE;
    }

    public function build(ReferenceSearchService $referenceSearchService): ChannelInterceptor
    {
        return new InMemoryQueueAcknowledgeInterceptor();
    }
}
