<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Support\ErrorMessage;
use Throwable;

/**
 * licence Apache-2.0
 */
class ErrorChannelInterceptor
{
    public function __construct(private MessageChannel $errorChannel, private LoggingGateway $loggingGateway)
    {
    }

    public function handle(MethodInvocation $methodInvocation, Message $requestMessage)
    {
        try {
            return $methodInvocation->proceed();
        } catch (Throwable $exception) {
            $this->loggingGateway->info(
                'Error occurred during handling message. Sending Message to handle it in predefined Error Channel.',
                $requestMessage,
                $exception,
            );

            $this->errorChannel->send(ErrorMessage::create(MessageHandlingException::fromOtherException($exception, $requestMessage)));

            $this->loggingGateway->info(
                'Message was sent to Error Channel successfully.',
                $requestMessage,
                $exception,
            );
        }
    }
}
