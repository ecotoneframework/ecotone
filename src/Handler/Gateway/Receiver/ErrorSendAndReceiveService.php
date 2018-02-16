<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Receiver;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\SendAndReceiveService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\ErrorMessage;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ErrorReplySender
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
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