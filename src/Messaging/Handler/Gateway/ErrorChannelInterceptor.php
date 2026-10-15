<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Throwable;

/**
 * licence Apache-2.0
 */
class ErrorChannelInterceptor
{
    public function __construct(
        private ErrorChannelService $errorChannelService,
        private MessageChannel      $errorChannel,
        private ?string             $errorChannelRoutingSlip = null,
    ) {
    }

    public function handle(MethodInvocation $methodInvocation, Message $requestMessage)
    {
        try {
            return $methodInvocation->proceed();
        } catch (Throwable $exception) {
            $this->errorChannelService->handle(
                $requestMessage,
                $exception,
                $this->errorChannel,
                null,
                $this->errorChannelRoutingSlip,
            );
        }
    }
}
