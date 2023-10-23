<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Support\ErrorMessage;
use Throwable;

class ErrorChannelInterceptor
{
    public function __construct(private MessageChannel $errorChannel)
    {
    }

    public function handle(MethodInvocation $methodInvocation, Message $requestMessage)
    {
        try {
            return $methodInvocation->proceed();
        } catch (Throwable $exception) {
            $this->errorChannel->send(ErrorMessage::create(MessageHandlingException::fromOtherException($exception, $requestMessage)));
        }
    }
}
