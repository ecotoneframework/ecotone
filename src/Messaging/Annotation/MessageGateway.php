<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;

#[\Attribute(\Attribute::TARGET_METHOD)]
class MessageGateway
{
    public string $requestChannel;
    public string $errorChannel;
    public int $replyTimeoutInMilliseconds;
    public array $requiredInterceptorNames;
    public ?string $replyContentType;

    public function __construct(string $requestChannel, string $errorChannel = "", int $replyTimeoutInMilliseconds = GatewayProxyBuilder::DEFAULT_REPLY_MILLISECONDS_TIMEOUT, array $requiredInterceptorNames = [], ?string $replyContentType = null)
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