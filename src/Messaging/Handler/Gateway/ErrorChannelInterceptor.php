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
    public const PRECEDENCE = -1000000;
    /**
     * @var MessageChannel|null
     */
    private $errorChannel;

    /**
     * ErrorChannelInterceptor constructor.
     * @param MessageChannel|null $errorChannel
     */
    public function __construct(?MessageChannel $errorChannel)
    {
        $this->errorChannel = $errorChannel;
    }

    public function handle(MethodInvocation $methodInvocation, Message $requestMessage)
    {
        try {
            return $methodInvocation->proceed();
        }catch (\Throwable $exception) {
            if (!$this->errorChannel) {
                if ($exception instanceof MessagingException && $exception->getCause()) {
                    throw $exception->getCause();
                }

                throw $exception;
            }

            if (!($exception instanceof MessagingException)) {
                $exception = MessageHandlingException::fromOtherException($exception, $requestMessage);
            }

            $this->errorChannel->send(ErrorMessage::create($exception));
        }
    }
}