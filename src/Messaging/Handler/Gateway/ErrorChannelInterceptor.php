<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Handler\Gateway;


use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\ErrorMessage;

class ErrorChannelInterceptor
{
    private \Ecotone\Messaging\MessageChannel $errorChannel;

    /**
     * ErrorChannelInterceptor constructor.
     * @param MessageChannel $errorChannel
     */
    public function __construct(MessageChannel $errorChannel)
    {
        $this->errorChannel = $errorChannel;
    }

    public function handle(MethodInvocation $methodInvocation, Message $requestMessage)
    {
        try {
            return $methodInvocation->proceed();
        }catch (\Throwable $exception) {
            $this->errorChannel->send(ErrorMessage::create(MessageHandlingException::fromOtherException($exception, $requestMessage)));
        }
    }
}