<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Handler\Gateway;


use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;

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