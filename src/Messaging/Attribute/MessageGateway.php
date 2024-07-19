<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class MessageGateway
{
    private string $requestChannel;
    private string $errorChannel;
    private int $replyTimeoutInMilliseconds;
    private array $requiredInterceptorNames;
    private ?string $replyContentType;

    public function __construct(string $requestChannel, string $errorChannel = '', int $replyTimeoutInMilliseconds = GatewayProxyBuilder::DEFAULT_REPLY_MILLISECONDS_TIMEOUT, array $requiredInterceptorNames = [], ?string $replyContentType = null)
    {
        $this->requestChannel             = $requestChannel;
        $this->errorChannel               = $errorChannel;
        $this->replyTimeoutInMilliseconds = $replyTimeoutInMilliseconds;
        $this->requiredInterceptorNames   = $requiredInterceptorNames;
        $this->replyContentType           = $replyContentType;
    }

    public function getRequestChannel(): string
    {
        return $this->requestChannel;
    }

    public function getErrorChannel(): string
    {
        return $this->errorChannel;
    }

    public function getReplyTimeoutInMilliseconds(): int
    {
        return $this->replyTimeoutInMilliseconds;
    }

    public function getRequiredInterceptorNames(): array
    {
        return $this->requiredInterceptorNames;
    }

    public function getReplyContentType(): ?string
    {
        return $this->replyContentType;
    }
}
