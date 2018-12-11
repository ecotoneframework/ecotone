<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ErrorReplySender
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ErrorSendAndReceiveService implements SendAndReceiveService
{
    /**
     * @var SendAndReceiveService
     */
    private $requestReplySender;
    /**
     * @var ?MessageChannel
     */
    private $errorChannel;

    /**
     * ErrorReplySender constructor.
     * @param SendAndReceiveService $requestReplySender
     * @param null|MessageChannel $errorChannel
     */
    private function __construct(SendAndReceiveService $requestReplySender, ?MessageChannel $errorChannel)
    {
        $this->requestReplySender = $requestReplySender;
        $this->errorChannel = $errorChannel;
    }

    /**
     * @param SendAndReceiveService $replySender
     * @param MessageChannel|null $errorChannel
     * @return ErrorSendAndReceiveService
     */
    public static function create(SendAndReceiveService $replySender, ?MessageChannel $errorChannel) : self
    {
        return new self($replySender, $errorChannel);
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        try{
            $this->requestReplySender->send($message);
        }catch (\Throwable $e) {
            if (!$this->errorChannel) {
                throw MessageHandlingException::fromOtherException($e, $message);
            }

            $this->errorChannel->send(ErrorMessage::createWithOriginalMessage($e, $message));
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareForSend(MessageBuilder $messageBuilder, InterfaceToCall $interfaceToCall): MessageBuilder
    {
        return $this->requestReplySender->prepareForSend($messageBuilder, $interfaceToCall);
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        $replyMessage = $this->requestReplySender->receiveReply();

        if (is_null($replyMessage)) {
            return null;
        }
        if ($replyMessage instanceof ErrorMessage) {
            throw MessageHandlingException::fromErrorMessage($replyMessage);
        }

        return $replyMessage;
    }
}