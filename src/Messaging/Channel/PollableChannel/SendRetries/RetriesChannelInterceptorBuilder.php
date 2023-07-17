<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\SendRetries;

use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\PrecedenceChannelInterceptor;

final class RetriesChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    public function __construct(private string $relatedChannel, private RetryTemplate $retryTemplate)
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
        return PrecedenceChannelInterceptor::COLLECTOR_PRECEDENCE;
    }

    public function build(ReferenceSearchService $referenceSearchService): ChannelInterceptor
    {
        return new SendRetryChannelInterceptor(
            $this->relatedChannel,
            $this->retryTemplate,
            $referenceSearchService->get(LoggingHandlerBuilder::LOGGER_REFERENCE)
        );
    }
}
